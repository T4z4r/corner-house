<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enquiry;
use App\Models\PaymentLink;
use App\Models\Reservation;
use App\Models\Setting;
use App\Services\Audit\AuditLogger;
use App\Services\Booking\BookingHoldService;
use App\Services\Booking\BookingService;
use App\Services\Payment\PaymentLinkService;
use App\Services\System\MailConfigurationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

class EnquiryController extends Controller
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly BookingService $bookingService,
        private readonly BookingHoldService $holds,
        private readonly PaymentLinkService $paymentLinks,
    ) {}

    public function index(Request $request): View
    {
        $query = Enquiry::query()
            ->with(['reservation', 'room', 'bookingHold'])
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $search = $request->string('search')->toString();

                $query->where(function (Builder $subQuery) use ($search): void {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('message', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), function (Builder $query) use ($request): void {
                $query->where('status', $request->string('status')->toString());
            })
            ->when($request->filled('type'), function (Builder $query) use ($request): void {
                $query->where('type', $request->string('type')->toString());
            })
            ->orderByDesc('id');

        $items = $query->paginate(20)->withQueryString();

        return view('admin.enquiries.index', [
            'items' => $items,
            'newCount' => Enquiry::new()->count(),
        ]);
    }

    public function markRead(Enquiry $enquiry): RedirectResponse
    {
        if ($enquiry->status !== Enquiry::STATUS_READ) {
            $enquiry->update(['status' => Enquiry::STATUS_READ]);
            $this->auditLogger->log('enquiry.read', 'enquiries', 'enquiry', (string) $enquiry->id);
        }

        return back()->with('status', 'Enquiry marked as read.');
    }

    /**
     * Approve a pending booking request.
     *
     * Creates the reservation from the enquiry (re-checking availability),
     * releases the enquiry hold, then emails the guest a 24-hour payment link
     * for the refundable deposit.
     */
    public function approve(Request $request, Enquiry $enquiry): RedirectResponse
    {
        if ($enquiry->type !== Enquiry::TYPE_BOOKING) {
            return back()->withErrors(['error' => 'Only booking requests can be approved.']);
        }

        if ($enquiry->reservation_id) {
            return back()->withErrors(['error' => 'This booking request has already been linked to a reservation.']);
        }

        try {
            $reservation = $this->createReservationFromEnquiry($enquiry);
        } catch (\DomainException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        $enquiry->update([
            'status' => Enquiry::STATUS_APPROVED,
            'reservation_id' => $reservation->id,
        ]);

        $this->releaseHold($enquiry);

        $paymentLink = $this->paymentLinks->createForReservation($reservation, null, $request->user()?->id);
        $this->sendApprovalMail($enquiry, $reservation, $paymentLink);

        $this->auditLogger->log('enquiry.approved', 'enquiries', 'enquiry', (string) $enquiry->id);

        return back()->with('status', 'Booking approved — '.$reservation->reference.' created and a 24-hour payment link emailed to the guest.');
    }

    /**
     * Decline a pending booking request, releasing its hold immediately.
     */
    public function decline(Enquiry $enquiry): RedirectResponse
    {
        if ($enquiry->type !== Enquiry::TYPE_BOOKING) {
            return back()->withErrors(['error' => 'Only booking requests can be declined.']);
        }

        if ($enquiry->reservation_id) {
            return back()->withErrors(['error' => 'This booking request is already linked to a reservation and cannot be declined.']);
        }

        $enquiry->update(['status' => Enquiry::STATUS_DECLINED]);

        $this->releaseHold($enquiry);
        $this->sendDeclineMail($enquiry);

        $this->auditLogger->log('enquiry.declined', 'enquiries', 'enquiry', (string) $enquiry->id);

        return back()->with('status', 'Booking request declined; the dates are released.');
    }

    public function destroy(Enquiry $enquiry): RedirectResponse
    {
        $enquiry->delete();
        $this->auditLogger->log('enquiry.deleted', 'enquiries', 'enquiry', (string) $enquiry->id);

        return back()->with('status', 'Enquiry deleted.');
    }

    /**
     * Build a hold-status reservation from a booking enquiry. The room's
     * availability is re-checked; the enquiry's own hold is ignored so an
     * in-window request does not conflict with itself.
     */
    private function createReservationFromEnquiry(Enquiry $enquiry): Reservation
    {
        $name = trim((string) $enquiry->name);
        $nameParts = $name ? preg_split('/\s+/', $name) : [];
        $firstName = trim((string) ($nameParts[0] ?? 'Guest')) ?: 'Guest';
        $lastName = trim((string) ($nameParts[1] ?? ''));
        $guests = max(1, (int) preg_replace('/\D/', '', (string) ($enquiry->guests ?? '')) ?: 1);

        $result = $this->bookingService->create([
            'room_id' => $enquiry->room_id,
            'check_in' => $enquiry->check_in->toDateString(),
            'check_out' => $enquiry->check_out->toDateString(),
            'guests_count' => $guests,
            'guest_first_name' => $firstName,
            'guest_last_name' => $lastName,
            'guest_email' => $enquiry->email,
            'guest_phone' => $enquiry->phone,
            'drinks_package' => (bool) $enquiry->drinks_package,
            'damage_deposit' => (float) Setting::getValue('damage_deposit', 950),
            'status' => 'hold',
            'source' => 'direct',
            'hold_token' => $enquiry->bookingHold?->hold_token,
            'notes' => 'Approved from website booking request #'.$enquiry->id.'.',
        ]);

        return $result['reservation'];
    }

    private function releaseHold(Enquiry $enquiry): void
    {
        if ($enquiry->bookingHold && $enquiry->bookingHold->status === 'active') {
            $this->holds->release($enquiry->bookingHold);
        }
    }

    /**
     * Best-effort: the booking is already stored, so a failed mail must not
     * roll back the approval.
     */
    private function sendApprovalMail(Enquiry $enquiry, Reservation $reservation, PaymentLink $paymentLink): void
    {
        try {
            app(MailConfigurationService::class)->apply();

            $deposit = (float) Setting::getValue('damage_deposit', 950);
            $balanceDue = max(0.0, round((float) $reservation->total_amount - $deposit, 2));
            $expiresAt = $paymentLink->expires_at;

            Mail::raw(
                'Hi '.$this->firstName($enquiry).",\n\n".
                'Great news — your booking request for '.($reservation->room?->name ?? 'Corner House')." at Corner House has been approved.\n\n".
                $reservation->check_in->format('d M Y').' → '.$reservation->check_out->format('d M Y').($enquiry->nights ? ' ('.$enquiry->nights." nights)\n\n" : "\n\n").
                'Pay your refundable deposit of £'.number_format($deposit, 2)." to confirm the dates:\n".
                $this->paymentLinks->urlFor($paymentLink)."\n\n".
                'This payment link expires '.$expiresAt->format('d M Y H:i').' ('.(int) Setting::getValue('payment_link_hours', 24).' hours).'."\n".
                ($balanceDue > 0 ? 'The balance of £'.number_format($balanceDue, 2).' is due before arrival.'."\n" : '')."\n".
                "Many thanks,\nCorner House",
                function ($message) use ($enquiry): void {
                    $message->to($enquiry->email)
                        ->subject('Your Corner House booking is approved');
                },
            );
        } catch (Throwable $e) {
            Log::warning("Approval email could not be sent for enquiry {$enquiry->id}.", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Best-effort: the enquiry is already updated, so a failed mail must not
     * roll back the decline.
     */
    private function sendDeclineMail(Enquiry $enquiry): void
    {
        try {
            app(MailConfigurationService::class)->apply();

            Mail::raw(
                'Hi '.$this->firstName($enquiry).",\n\n".
                'Thank you for your booking request for Corner House'.($enquiry->check_in ? ' on '.$enquiry->check_in->format('d M Y').' → '.($enquiry->check_out?->format('d M Y') ?? '') : '').".\n\n".
                "We are sorry, but we are unable to accept this request and the dates have been released.\n\n".
                "If you would like to discuss alternative dates, or have any questions, just reply to this email and we will do our best to help.\n\n".
                "Many thanks,\nCorner House",
                function ($message) use ($enquiry): void {
                    $message->to($enquiry->email)
                        ->subject('Your Corner House booking request');
                },
            );
        } catch (Throwable $e) {
            Log::warning("Decline email could not be sent for enquiry {$enquiry->id}.", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function firstName(Enquiry $enquiry): string
    {
        $name = trim((string) $enquiry->name);

        if ($name === '') {
            return 'there';
        }

        $nameParts = preg_split('/\s+/', $name);

        return (string) $nameParts[0];
    }
}
