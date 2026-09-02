<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Room;
use App\Services\Audit\AuditLogger;
use App\Services\Booking\BookingService;
use App\Services\Notification\SystemNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReservationController extends Controller
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly BookingService $bookingService,
        private readonly SystemNotificationService $systemNotifications,
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
        ]);
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
