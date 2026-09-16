<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\FetchBeds24BookingsJob;
use App\Mail\PaymentLinkMail;
use App\Models\Reservation;
use App\Models\Room;
use App\Services\Audit\AuditLogger;
use App\Services\Booking\BookingService;
use App\Services\Notification\SystemNotificationService;
use App\Services\Payment\PaymentLinkService;
use App\Services\System\MailConfigurationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ReservationController extends Controller
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly BookingService $bookingService,
        private readonly SystemNotificationService $systemNotifications,
        private readonly PaymentLinkService $paymentLinks,
    ) {}

    public function index(Request $request): View
    {
        $query = Reservation::query()->with(['property', 'room', 'guest'])->latest();

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($source = $request->query('source')) {
            $query->where('source', $source);
        }
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search): void {
                $q->where('reference', 'like', "%{$search}%")
                    ->orWhereHas('guest', fn ($g) => $g->where('last_name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            });
        }
        if ($checkInFrom = $request->query('check_in_from')) {
            $query->where('check_in', '>=', $checkInFrom);
        }
        if ($checkInTo = $request->query('check_in_to')) {
            $query->where('check_in', '<=', $checkInTo);
        }

        $reservations = $query->paginate(20)->withQueryString();

        return view('admin.reservations.index', ['reservations' => $reservations]);
    }

    public function fetchFromBeds24(): RedirectResponse
    {
        try {
            Bus::dispatchSync(new FetchBeds24BookingsJob);
        } catch (Throwable $e) {
            return back()->withErrors(['error' => 'Beds24 bookings fetch failed: '.$e->getMessage()]);
        }

        $this->auditLogger->log('channels.fetch_bookings', 'channels');

        return back()->with('status', 'Beds24 bookings fetched. New and changed bookings have been imported.');
    }

    public function export(Request $request): StreamedResponse|View
    {
        $query = Reservation::query()->with(['property', 'room', 'guest'])->latest();

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($source = $request->query('source')) {
            $query->where('source', $source);
        }
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search): void {
                $q->where('reference', 'like', "%{$search}%")
                    ->orWhereHas('guest', fn ($g) => $g->where('last_name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            });
        }
        if ($checkInFrom = $request->query('check_in_from')) {
            $query->where('check_in', '>=', $checkInFrom);
        }
        if ($checkInTo = $request->query('check_in_to')) {
            $query->where('check_in', '<=', $checkInTo);
        }

        $reservations = $query->get();
        $format = $request->query('format', 'csv');
        $filename = 'bookings-'.Str::slug(now()->format('Y-m-d H-i')).'.'.$format;

        if ($format === 'html') {
            return view('admin.reservations.export-pdf', [
                'reservations' => $reservations,
                'filters' => $request->only(['status', 'source', 'search', 'check_in_from', 'check_in_to']),
            ]);
        }

        return response()->streamDownload(function () use ($reservations): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Reference',
                'Guest',
                'Email',
                'Room',
                'Check-in',
                'Check-out',
                'Nights',
                'Guests',
                'Total',
                'Paid',
                'Payment Status',
                'Source',
                'Status',
                'Created',
            ]);
            foreach ($reservations as $r) {
                fputcsv($handle, [
                    $r->reference,
                    $r->guest?->full_name ?? '',
                    $r->guest?->email ?? '',
                    $r->room?->name ?? '',
                    $r->check_in->format('d/m/Y'),
                    $r->check_out->format('d/m/Y'),
                    $r->check_in->diffInDays($r->check_out),
                    $r->guests_count,
                    $r->total_amount,
                    $r->paid_amount,
                    ucfirst($r->payment_status),
                    ucfirst($r->source),
                    ucfirst(str_replace('_', ' ', $r->status)),
                    $r->created_at->format('d/m/Y H:i'),
                ]);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function create(): View
    {
        return view('admin.reservations.create', ['rooms' => Room::query()->where('status', 'active')->with('property')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        try {
            $data = $this->validated($request);
            $data['status'] = 'confirmed';
            $data['source'] = 'manual';

            $result = $this->bookingService->create($data);
            $this->systemNotifications->reservationCreated($result['reservation'], $request->user()?->id);

            $this->auditLogger->log('reservations.created', 'reservations', 'reservation', (string) $result['reservation']->id);

            return redirect()->route('admin.reservations.show', $result['reservation'])
                ->with('status', 'Reservation '.$result['reservation']->reference.' created.');
        } catch (\DomainException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function show(Reservation $reservation): View
    {
        return view('admin.reservations.show', [
            'reservation' => $reservation->load(['property', 'room', 'guest', 'guests']),
            'paymentLink' => $this->paymentLinks->activeFor($reservation),
        ]);
    }

    /**
     * Create an expiring payment link (default 24 hours) and email it to the guest.
     */
    public function sendPaymentLink(Request $request, Reservation $reservation): RedirectResponse
    {
        if (! $reservation->guest?->email) {
            return back()->withErrors(['error' => 'This booking has no guest email to send the payment link to.']);
        }

        try {
            app(MailConfigurationService::class)->apply();

            $paymentLink = $this->paymentLinks->createForReservation($reservation, null, $request->user()?->id);
            $expiresAt = $paymentLink->expires_at;

            Mail::to($reservation->guest->email)->send(new PaymentLinkMail($reservation, $paymentLink));

            $this->auditLogger->log('reservations.payment_link_sent', 'reservations', 'reservation', (string) $reservation->id);

            return back()->with('status', 'Payment link emailed to '.$reservation->guest->email.'. It expires '.$expiresAt->format('d M Y H:i').'.');
        } catch (Throwable $e) {
            Log::warning('Failed to email payment link for reservation '.$reservation->id, [
                'message' => $e->getMessage(),
            ]);

            return back()->withErrors(['error' => 'The payment link could not be emailed. Please check the mail configuration.']);
        }
    }

    public function edit(Reservation $reservation): View
    {
        return view('admin.reservations.edit', [
            'reservation' => $reservation->load(['guest']),
            'rooms' => Room::query()->where('status', 'active')->with('property')->get(),
        ]);
    }

    public function update(Request $request, Reservation $reservation): RedirectResponse
    {
        try {
            $data = $this->validated($request);
            $data['source'] = $reservation->source;

            $this->bookingService->update($reservation, $data);
            $this->systemNotifications->reservationUpdated($reservation->refresh(), $request->user()?->id);
            $this->auditLogger->log('reservations.updated', 'reservations', 'reservation', (string) $reservation->id);

            return redirect()->route('admin.reservations.show', $reservation)
                ->with('status', 'Reservation '.$reservation->reference.' updated.');
        } catch (\DomainException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function destroy(Request $request, Reservation $reservation): RedirectResponse
    {
        try {
            $reference = $reservation->reference;
            $this->bookingService->delete($reservation);
            $this->auditLogger->log('reservations.deleted', 'reservations', 'reservation', (string) $reservation->id);

            return redirect()->route('admin.reservations.index')->with('status', 'Booking '.$reference.' deleted.');
        } catch (\DomainException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function cancel(Request $request, Reservation $reservation): RedirectResponse
    {
        try {
            $this->bookingService->cancel($reservation, $request->input('reason'), auth()->id());
            $this->systemNotifications->reservationCancelled($reservation->fresh(), auth()->id());
            $this->auditLogger->log('reservations.cancelled', 'reservations', 'reservation', (string) $reservation->id);

            return redirect()->route('admin.reservations.show', $reservation)->with('status', 'Reservation cancelled.');
        } catch (\DomainException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function checkIn(Reservation $reservation): RedirectResponse
    {
        if ($reservation->status !== 'confirmed') {
            return back()->withErrors(['error' => 'Reservation must be confirmed before check-in.']);
        }
        $reservation->update(['status' => 'checked_in']);
        $this->systemNotifications->reservationCheckedIn($reservation, auth()->id());
        $this->auditLogger->log('reservations.checked_in', 'reservations', 'reservation', (string) $reservation->id);

        return back()->with('status', 'Guest checked in.');
    }

    public function checkOut(Reservation $reservation): RedirectResponse
    {
        if (! in_array($reservation->status, ['checked_in', 'confirmed'])) {
            return back()->withErrors(['error' => 'Reservation is not checked in.']);
        }
        $reservation->update(['status' => 'checked_out']);
        $this->systemNotifications->reservationCheckedOut($reservation, auth()->id());
        $this->auditLogger->log('reservations.checked_out', 'reservations', 'reservation', (string) $reservation->id);

        return back()->with('status', 'Guest checked out.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'room_id' => ['required', 'exists:rooms,id'],
            'check_in' => ['required', 'date'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'guests_count' => ['required', 'integer', 'min:1'],
            'guest_email' => ['nullable', 'email'],
            'guest_first_name' => ['nullable', 'string', 'max:255'],
            'guest_last_name' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
