<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\Room;
use App\Services\Availability\AvailabilityService;
use App\Services\Booking\BookingHoldService;
use App\Services\Booking\BookingService;
use App\Services\Payment\PaymentService;
use App\Services\Pricing\PricingEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function __construct(
        private readonly AvailabilityService $availability,
        private readonly PricingEngine $pricing,
        private readonly BookingHoldService $holds,
        private readonly BookingService $bookings,
        private readonly PaymentService $payments,
    ) {}

    public function search(Request $request): View
    {
        $property = Property::query()->where('status', 'active')->first();
        $rooms = collect();

        $checkIn = $request->query('check_in');
        $checkOut = $request->query('check_out');
        $guests = (int) $request->query('guests', 1);

        if ($property && $checkIn && $checkOut) {
            $start = Carbon::parse($checkIn)->startOfDay();
            $end = Carbon::parse($checkOut)->startOfDay();

            if ($end->gt($start)) {
                $rooms = $this->availability->listAvailableRooms($property->id, $start, $end, max(1, $guests))
                    ->map(function (Room $room) use ($start, $end, $guests): Room {
                        $quote = $this->pricing->calculateForRange($room, $start, $end, $guests);
                        $room->setAttribute('quote', $quote);

                        return $room;
                    });
            }
        }

        return view('website.booking.search', [
            'property' => $property,
            'rooms' => $rooms,
            'checkIn' => $checkIn,
            'checkOut' => $checkOut,
            'guests' => $guests,
        ]);
    }

    public function details(Request $request, Room $room): View|RedirectResponse
    {
        $data = $request->validate([
            'check_in' => ['required', 'date'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'guests' => ['required', 'integer', 'min:1'],
        ]);

        $checkIn = Carbon::parse($data['check_in']);
        $checkOut = Carbon::parse($data['check_out']);
        $quote = $this->pricing->calculateForRange($room, $checkIn, $checkOut, (int) $data['guests']);

        if ($checkIn->diffInDays($checkOut) < $quote['minimum_stay']) {
            return back()->withErrors(['check_in' => 'This stay does not meet the '.$quote['minimum_stay'].'-night minimum.']);
        }

        if (($quote['maximum_stay'] ?? null) !== null && $checkIn->diffInDays($checkOut) > $quote['maximum_stay']) {
            return back()->withErrors(['check_in' => 'This stay exceeds the '.$quote['maximum_stay'].'-night maximum.']);
        }

        $available = $this->availability->isRoomAvailable($room, $checkIn, $checkOut);

        if (! $available['available']) {
            return redirect()->route('booking.search')->withErrors(['error' => implode('; ', $available['conflicts'])]);
        }

        return view('website.booking.details', [
            'room' => $room->load('property', 'images'),
            'checkIn' => $checkIn,
            'checkOut' => $checkOut,
            'guests' => (int) $data['guests'],
            'quote' => $quote,
        ]);
    }

    public function holdAndPay(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'room_id' => ['required', 'exists:rooms,id'],
            'check_in' => ['required', 'date'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'guests_count' => ['required', 'integer', 'min:1'],
            'guest_first_name' => ['required', 'string', 'max:255'],
            'guest_last_name' => ['required', 'string', 'max:255'],
            'guest_email' => ['required', 'email'],
            'guest_phone' => ['nullable', 'string', 'max:50'],
        ]);

        $room = Room::query()->findOrFail($data['room_id']);
        $checkIn = Carbon::parse($data['check_in']);
        $checkOut = Carbon::parse($data['check_out']);
        $quote = $this->pricing->calculateForRange($room, $checkIn, $checkOut, (int) $data['guests_count']);

        if ($checkIn->diffInDays($checkOut) < $quote['minimum_stay']) {
            return back()->withInput()->withErrors(['error' => 'This stay does not meet the '.$quote['minimum_stay'].'-night minimum.']);
        }

        if (($quote['maximum_stay'] ?? null) !== null && $checkIn->diffInDays($checkOut) > $quote['maximum_stay']) {
            return back()->withInput()->withErrors(['error' => 'This stay exceeds the '.$quote['maximum_stay'].'-night maximum.']);
        }

        try {
            $hold = $this->holds->createHold(
                $room->id,
                $checkIn,
                $checkOut,
                $request->session()->getId(),
                $quote['total'],
            );

            $result = $this->bookings->create([
                ...$data,
                'status' => 'hold',
                'source' => 'direct',
                'hold_token' => $hold['hold']->hold_token,
            ]);

            $payment = $this->payments->startCheckout(
                $result['reservation'],
                route('booking.confirmation').'?session_id={CHECKOUT_SESSION_ID}',
                route('booking.search', [
                    'check_in' => $data['check_in'],
                    'check_out' => $data['check_out'],
                    'guests' => $data['guests_count'],
                ]),
            );

            $url = $this->payments->checkoutUrl($payment);

            if (! $url) {
                throw new \DomainException('Unable to start payment.');
            }

            $request->session()->put('booking.reservation_id', $result['reservation']->id);

            return redirect()->away($url);
        } catch (\DomainException $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function confirmation(Request $request): View
    {
        $reservation = null;

        if ($sessionId = $request->query('session_id')) {
            try {
                $payment = $this->payments->confirmFromSession($sessionId);
                $reservation = $payment->reservation;
            } catch (\Throwable) {
                $reservation = Reservation::query()
                    ->whereHas('payments', fn ($q) => $q->where('provider_session_id', $sessionId))
                    ->first();
            }
        }

        $reservation ??= Reservation::query()->find($request->session()->get('booking.reservation_id'));

        return view('website.booking.confirmation', [
            'reservation' => $reservation?->load(['room', 'guest', 'property']),
        ]);
    }

    public function calculatePrice(Request $request): JsonResponse
    {
        $data = $request->validate([
            'room_id' => ['required', 'exists:rooms,id'],
            'check_in' => ['required', 'date'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'guests' => ['nullable', 'integer', 'min:1'],
        ]);

        $room = Room::query()->findOrFail($data['room_id']);
        $quote = $this->pricing->calculateForRange(
            $room,
            Carbon::parse($data['check_in']),
            Carbon::parse($data['check_out']),
            (int) ($data['guests'] ?? 1),
        );

        return response()->json($quote);
    }

    public function availability(Request $request): JsonResponse
    {
        $data = $request->validate([
            'property_id' => ['nullable', 'exists:properties,id'],
            'check_in' => ['required', 'date'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'guests' => ['nullable', 'integer', 'min:1'],
        ]);

        $propertyId = $data['property_id'] ?? Property::query()->where('status', 'active')->value('id');

        if (! $propertyId) {
            return response()->json(['rooms' => []]);
        }

        $rooms = $this->availability->listAvailableRooms(
            (int) $propertyId,
            Carbon::parse($data['check_in']),
            Carbon::parse($data['check_out']),
            (int) ($data['guests'] ?? 1),
        )->map(fn (Room $room): array => [
            'id' => $room->id,
            'name' => $room->name,
            'capacity' => $room->capacity,
            'base_rate' => $room->base_rate,
        ]);

        return response()->json(['rooms' => $rooms->values()]);
    }

    public function createHold(Request $request): JsonResponse
    {
        $data = $request->validate([
            'room_id' => ['required', 'exists:rooms,id'],
            'check_in' => ['required', 'date'],
            'check_out' => ['required', 'date', 'after:check_in'],
        ]);

        $room = Room::query()->findOrFail($data['room_id']);
        $checkIn = Carbon::parse($data['check_in']);
        $checkOut = Carbon::parse($data['check_out']);
        $quote = $this->pricing->calculateForRange($room, $checkIn, $checkOut);

        try {
            $sessionId = $request->input('session_id')
                ?? ($request->hasSession() ? $request->session()->getId() : (string) Str::uuid());

            $hold = $this->holds->createHold(
                $room->id,
                $checkIn,
                $checkOut,
                $sessionId,
                $quote['total'],
            );

            return response()->json([
                'hold_token' => $hold['hold']->hold_token,
                'expires_at' => $hold['expires_at']->toIso8601String(),
                'quoted_total' => $quote['total'],
            ]);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
