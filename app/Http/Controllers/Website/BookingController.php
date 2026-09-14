<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\AddOn;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\Setting;
use App\Services\Availability\AvailabilityService;
use App\Services\Booking\BookingHoldService;
use App\Services\Booking\BookingService;
use App\Services\Payment\PaymentService;
use App\Services\Pricing\PricingEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
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
        $activeProperty = Property::query()->where('status', 'active')->orderByDesc('is_primary')->first();

        $requestedPropertyId = $request->query('property_id');
        $property = $activeProperty && $requestedPropertyId
            ? Property::query()->where('status', 'active')->find($requestedPropertyId)
            : null;
        $property ??= $activeProperty;

        $rooms = collect();

        $checkIn = $request->query('check_in');
        $checkOut = $request->query('check_out');
        $guests = (int) $request->query('guests', 1);

        if ($property && $checkIn && $checkOut) {
            $start = Carbon::parse($checkIn)->startOfDay();
            $end = Carbon::parse($checkOut)->startOfDay();

            if ($end->gt($start)) {
                $propertyRooms = Room::query()
                    ->with('property')
                    ->where('property_id', $property->id)
                    ->where('status', 'active')
                    ->get();
                $houseRoom = $propertyRooms->first();
                $houseAvailable = $propertyRooms->isNotEmpty()
                    && $propertyRooms->every(fn (Room $room): bool => $this->availability->isRoomAvailable($room, $start, $end)['available']);

                if ($houseRoom && $houseAvailable && $guests <= (int) $property->capacity) {
                    $quote = $this->pricing->calculateForRange($houseRoom, $start, $end, $guests, null, true);
                    $houseRoom->setAttribute('quote', $quote);
                    $houseRoom->setAttribute('house_name', $property->name);
                    $houseRoom->setAttribute('house_capacity', $property->capacity);
                    $houseRoom->load(['property', 'images' => fn ($q) => $q->orderBy('sort_order')]);
                    $rooms = collect([$houseRoom]);
                }
            }
        }

        // Linked properties are duplicate listings of the same accommodation,
        // so the search page lets the guest pick which listing to book.
        $availableProperties = $property ? Property::query()
            ->whereIn('id', $property->linkedPropertyIds())
            ->where('status', 'active')
            ->orderByDesc('is_primary')
            ->get(['id', 'name'])
            : collect();

        return view('website.booking.search', [
            'property' => $property,
            'availableProperties' => $availableProperties,
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

        // 24-hour advance notice
        $minAdvanceDays = (int) Setting::getValue('min_advance_days', 1);
        if ($checkIn->lt(Carbon::today()->addDays($minAdvanceDays))) {
            return back()->withErrors(['check_in' => 'Bookings require at least '.$minAdvanceDays.' days advance notice.']);
        }

        // No same-day turnaround: if checkout is today, next available is tomorrow
        // (already enforced by min_advance_days, but explicit check for turnaround)
        $lastCheckoutToday = Reservation::query()
            ->where('room_id', $room->id)
            ->whereIn('status', ['confirmed', 'checked_in', 'checked_out'])
            ->whereDate('check_out', Carbon::today())
            ->exists();
        if ($lastCheckoutToday && $checkIn->lte(Carbon::today())) {
            return back()->withErrors(['check_in' => 'This room is being turned over. The next available check-in date is '.Carbon::tomorrow()->format('d M Y').'.']);
        }

        $quote = $this->pricing->calculateForRange($room, $checkIn, $checkOut, (int) $data['guests'], null, true);

        if ($checkIn->diffInDays($checkOut) < $quote['minimum_stay']) {
            return back()->withErrors(['check_in' => 'This stay does not meet the '.$quote['minimum_stay'].'-night minimum.']);
        }

        if (($quote['maximum_stay'] ?? null) !== null && $checkIn->diffInDays($checkOut) > $quote['maximum_stay']) {
            return back()->withErrors(['check_in' => 'This stay exceeds the '.$quote['maximum_stay'].'-night maximum.']);
        }

        // The damage deposit is collected up front with the stay, so it must be
        // shown (and included in the total) here to match what holdAndPay charges.
        $damageDeposit = (float) Setting::getValue('damage_deposit', 950);
        $quote['damage_deposit'] = $damageDeposit;
        $quote['total'] = round($quote['total'] + $damageDeposit, 2);

        // Max occupancy
        $maxAdults = (int) Setting::getValue('max_adults', 12);
        if ((int) $data['guests'] > $maxAdults) {
            return back()->withErrors(['guests' => 'Maximum '.$maxAdults.' guests allowed.']);
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

    public function holdAndPay(Request $request): RedirectResponse|JsonResponse
    {
        if (! $request->has('guest_first_name') && $request->has('name')) {
            $parts = explode(' ', trim((string) $request->input('name')), 2);
            $request->merge([
                'guest_first_name' => $parts[0] ?? 'Guest',
                'guest_last_name' => $parts[1] ?? ($parts[0] ?? 'Guest'),
            ]);
        }

        if (! $request->has('guest_email') && $request->has('email')) {
            $request->merge(['guest_email' => (string) $request->input('email')]);
        }

        if (! $request->has('guest_phone') && $request->has('phone')) {
            $request->merge(['guest_phone' => (string) $request->input('phone')]);
        }

        if (! $request->has('check_in') && $request->has('checkIn')) {
            $request->merge(['check_in' => (string) $request->input('checkIn')]);
        }

        if (! $request->has('check_out') && $request->has('checkOut')) {
            $request->merge(['check_out' => (string) $request->input('checkOut')]);
        }

        if (! $request->has('guests_count')) {
            $rawGuests = $request->input('guests', $request->input('guests_count', 12));
            $guestsNum = (int) preg_replace('/[^0-9]/', '', (string) $rawGuests) ?: 12;
            $request->merge(['guests_count' => $guestsNum]);
        }

        if (! $request->has('room_id')) {
            $defaultRoomId = Room::query()->where('status', 'active')->value('id');
            if ($defaultRoomId) {
                $request->merge(['room_id' => $defaultRoomId]);
            }
        }

        try {
            $data = $request->validate([
                'room_id' => ['required', 'exists:rooms,id'],
                'check_in' => ['required', 'date'],
                'check_out' => ['required', 'date', 'after:check_in'],
                'guests_count' => ['required', 'integer', 'min:1'],
                'guest_first_name' => ['required', 'string', 'max:255'],
                'guest_last_name' => ['required', 'string', 'max:255'],
                'guest_email' => ['required', 'email'],
                'guest_phone' => ['nullable', 'string', 'max:50'],
                'addon_ids' => ['nullable', 'array'],
                'addon_ids.*' => ['integer', 'exists:add_ons,id'],
            ]);
        } catch (ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json(['error' => implode(' ', array_merge(...array_values($e->errors())))], 422);
            }

            throw $e;
        }

        $room = Room::query()->findOrFail($data['room_id']);
        $checkIn = Carbon::parse($data['check_in']);
        $checkOut = Carbon::parse($data['check_out']);

        // 24-hour advance notice
        $minAdvanceDays = (int) Setting::getValue('min_advance_days', 1);
        if ($checkIn->lt(Carbon::today()->addDays($minAdvanceDays))) {
            $msg = 'Bookings require at least '.$minAdvanceDays.' days advance notice.';
            if ($request->expectsJson()) {
                return response()->json(['error' => $msg], 422);
            }

            return back()->withInput()->withErrors(['error' => $msg]);
        }

        // Max occupancy
        $maxAdults = (int) Setting::getValue('max_adults', 12);
        if ((int) $data['guests_count'] > $maxAdults) {
            $msg = 'Maximum '.$maxAdults.' adults allowed.';
            if ($request->expectsJson()) {
                return response()->json(['error' => $msg], 422);
            }

            return back()->withInput()->withErrors(['error' => $msg]);
        }

        $maxInfants = (int) Setting::getValue('max_infants', 2);
        $maxCots = (int) Setting::getValue('max_cots', 2);
        $infants = (int) ($data['infants'] ?? 0);
        $cots = (int) ($data['cots'] ?? 0);

        if ($infants > $maxInfants) {
            $msg = 'Maximum '.$maxInfants.' infants allowed.';
            if ($request->expectsJson()) {
                return response()->json(['error' => $msg], 422);
            }

            return back()->withInput()->withErrors(['error' => $msg]);
        }
        if ($cots > $maxCots) {
            $msg = 'Maximum '.$maxCots.' cots allowed.';
            if ($request->expectsJson()) {
                return response()->json(['error' => $msg], 422);
            }

            return back()->withInput()->withErrors(['error' => $msg]);
        }

        $quote = $this->pricing->calculateForRange($room, $checkIn, $checkOut, (int) $data['guests_count'], null, true);

        if ($checkIn->diffInDays($checkOut) < $quote['minimum_stay']) {
            $msg = 'This stay does not meet the '.$quote['minimum_stay'].'-night minimum.';
            if ($request->expectsJson()) {
                return response()->json(['error' => $msg], 422);
            }

            return back()->withInput()->withErrors(['error' => $msg]);
        }

        if (($quote['maximum_stay'] ?? null) !== null && $checkIn->diffInDays($checkOut) > $quote['maximum_stay']) {
            $msg = 'This stay exceeds the '.$quote['maximum_stay'].'-night maximum.';
            if ($request->expectsJson()) {
                return response()->json(['error' => $msg], 422);
            }

            return back()->withInput()->withErrors(['error' => $msg]);
        }

        // Add damage deposit to total
        $damageDeposit = (float) Setting::getValue('damage_deposit', 950);
        $quote['total'] = round($quote['total'] + $damageDeposit, 2);
        $quote['damage_deposit'] = $damageDeposit;

        // Calculate add-ons total
        $addonIds = $data['addon_ids'] ?? [];
        $addons = AddOn::query()->whereIn('id', $addonIds)->where('is_active', true)->get();
        $addonsTotal = (float) $addons->sum('price');
        $quote['addons_total'] = $addonsTotal;
        $quote['total'] = round($quote['total'] + $addonsTotal, 2);

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
                'damage_deposit' => $damageDeposit,
                'addons_total' => $addonsTotal,
            ]);

            // Attach add-ons to reservation
            $reservation = $result['reservation'];
            foreach ($addons as $addon) {
                $reservation->addons()->attach($addon->id, [
                    'quantity' => 1,
                    'unit_price' => $addon->price,
                    'total_price' => $addon->price,
                ]);
            }

            $reservation->load(['room', 'guest', 'property', 'addons']);

            $payment = $this->payments->startCheckout(
                $reservation,
                route('booking.confirmation').'?session_id={CHECKOUT_SESSION_ID}',
                route('booking.checkout', $reservation->id).'?cancelled=1',
            );

            $url = $this->payments->checkoutUrl($payment);

            $request->session()->put('booking.reservation_id', $reservation->id);

            $checkoutRoute = route('booking.checkout', $reservation->id);

            if ($request->expectsJson()) {
                return response()->json(['url' => $checkoutRoute, 'stripe_url' => $url, 'status' => 'ok']);
            }

            return redirect()->route('booking.checkout', $reservation->id);
        } catch (\Throwable $e) {
            Log::error('Direct booking Stripe payment error', [
                'message' => $e->getMessage(),
            ]);

            if ($request->expectsJson()) {
                return response()->json(['error' => $e->getMessage()], 422);
            }

            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function checkoutPage(Reservation $reservation): View|RedirectResponse
    {
        $reservation->load(['room.images', 'guest', 'property', 'addons', 'payments']);

        $latestPayment = $reservation->payments()->latest()->first();

        if ($reservation->isPaid() || ($latestPayment && $latestPayment->isPaid())) {
            return redirect()->route('booking.confirmation', [
                'session_id' => $latestPayment?->provider_session_id ?? 'paid',
            ]);
        }

        // Build Stripe Checkout Session URL for the hosted option.
        $checkoutUrl = null;
        if ($latestPayment && ! blank($latestPayment->metadata['checkout_url'] ?? null)) {
            $checkoutUrl = $this->payments->checkoutUrl($latestPayment);
        }

        if (! $checkoutUrl) {
            try {
                $payment = $this->payments->startCheckout(
                    $reservation,
                    route('booking.confirmation').'?session_id={CHECKOUT_SESSION_ID}',
                    route('booking.checkout', $reservation->id).'?cancelled=1',
                );
                $checkoutUrl = $this->payments->checkoutUrl($payment);
            } catch (\Throwable $e) {
                Log::warning('Failed to generate Stripe checkout session', [
                    'message' => $e->getMessage(),
                ]);
            }
        }

        // Prepare a Payment Intent so guests can enter card details directly.
        $paymentIntentSecret = null;
        $paymentIntentId = null;
        try {
            $intentPayment = $this->payments->createIntent($reservation);
            $paymentIntentSecret = $this->payments->clientSecret($intentPayment);
            $paymentIntentId = $intentPayment->provider_payment_id;
        } catch (\Throwable $e) {
            Log::warning('Failed to prepare Stripe payment intent', [
                'message' => $e->getMessage(),
            ]);
        }

        $stripeKey = Setting::getValue('stripe_key');
        if (blank($stripeKey)) {
            $stripeKey = config('services.stripe.key', '');
        }

        $paymentReturnUrl = $paymentIntentId
            ? route('booking.confirmation', ['payment_intent' => $paymentIntentId])
            : null;

        return view('website.booking.checkout', [
            'reservation' => $reservation,
            'checkoutUrl' => $checkoutUrl,
            'stripeKey' => $stripeKey,
            'paymentIntentSecret' => $paymentIntentSecret,
            'paymentReturnUrl' => $paymentReturnUrl,
        ]);
    }

    public function confirmDirectPayment(Request $request, Reservation $reservation): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'payment_intent_id' => ['required', 'string'],
        ]);

        try {
            $payment = $this->payments->confirmFromIntent($data['payment_intent_id']);

            if ($payment->reservation_id !== $reservation->id) {
                throw new \DomainException('Payment does not belong to this reservation.');
            }
        } catch (\Throwable $e) {
            if ($request->expectsJson()) {
                return response()->json(['error' => $e->getMessage()], 422);
            }

            return back()->withErrors(['error' => $e->getMessage()]);
        }

        $request->session()->put('booking.reservation_id', $reservation->id);

        if ($request->expectsJson()) {
            return response()->json(['status' => 'ok']);
        }

        return redirect()->route('booking.confirmation', [
            'payment_intent' => $payment->provider_payment_id,
        ]);
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

        if (! $reservation && $intentId = $request->query('payment_intent')) {
            try {
                $payment = $this->payments->confirmFromIntent($intentId);
                $reservation = $payment->reservation;
            } catch (\Throwable) {
                $reservation = Reservation::query()
                    ->whereHas('payments', fn ($q) => $q->where('provider_payment_id', $intentId))
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
            'property_id' => (int) $room->property_id,
            'property_name' => $room->property?->name,
        ]);

        return response()->json(['rooms' => $rooms->values()]);
    }

    /**
     * Public JSON endpoint returning per-night rates and a quote summary for a
     * date range. The website booking widget calls this to display live prices
     * that reflect admin-set calendar overrides, pricing rules, and seasonal
     * adjustments instead of static fallback rates.
     */
    public function prices(Request $request): JsonResponse
    {
        $data = $request->validate([
            'room_id' => ['nullable', 'exists:rooms,id'],
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after:start'],
        ]);

        $room = ($data['room_id'] ?? null)
            ? Room::query()->where('status', 'active')->find($data['room_id'])
            : Room::query()->where('status', 'active')->orderBy('id')->first();

        if (! $room) {
            return response()->json(['error' => 'No active room available.'], 404);
        }

        $start = Carbon::parse($data['start'])->startOfDay();
        $end = Carbon::parse($data['end'])->startOfDay();

        $quote = $this->pricing->calculateForRange($room, $start, $end, 12, null, true);

        return response()->json([
            'room_id' => $room->id,
            'per_night' => $quote['per_night'],
            'base_amount' => $quote['base_amount'],
            'discount_amount' => $quote['discount_amount'],
            'fees_amount' => $quote['fees_amount'],
            'nights' => $quote['nights'],
        ]);
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
