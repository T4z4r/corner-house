<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Mail\NewBookingRequestMail;
use App\Models\AddOn;
use App\Models\Enquiry;
use App\Models\PaymentLink;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\Setting;
use App\Services\Availability\AvailabilityService;
use App\Services\Booking\BookingHoldService;
use App\Services\Payment\PaymentService;
use App\Services\Pricing\PricingEngine;
use App\Services\System\MailConfigurationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class BookingController extends Controller
{
    public function __construct(
        private readonly AvailabilityService $availability,
        private readonly PricingEngine $pricing,
        private readonly BookingHoldService $holds,
        private readonly PaymentService $payments,
    ) {}

    public function search(Request $request): View
    {
        $activeProperty = Property::query()->where('status', 'active')->orderByDesc('is_primary')->first();

        $requestedPropertyId = $request->query('property_id');
        $property = null;

        if ($activeProperty && $requestedPropertyId) {
            $decodedPropertyId = Property::decodeHashId((string) $requestedPropertyId);

            if ($decodedPropertyId !== null) {
                $property = Property::query()->where('status', 'active')->find($decodedPropertyId);
            }
        }

        $rooms = collect();

        $checkIn = $request->query('check_in');
        $checkOut = $request->query('check_out');
        $guests = (int) $request->query('guests', 1);

        if ($checkIn && $checkOut) {
            $start = Carbon::parse($checkIn)->startOfDay();
            $end = Carbon::parse($checkOut)->startOfDay();

            if ($end->gt($start)) {
                // Without an explicit property, default to the preferred whole-house
                // listing: the marina listing when its room is free, otherwise the
                // primary Corner House listing (Lion).
                if ($property === null && $activeProperty) {
                    $property = $this->preferredSearchProperty($start, $end, $guests) ?? $activeProperty;
                }

                if ($property) {
                    $propertyRooms = Room::query()
                        ->with('property')
                        ->where('property_id', $property->id)
                        ->where('status', 'active')
                        ->get();
                    $houseRoom = $propertyRooms->first();
                    $houseAvailable = $propertyRooms->isNotEmpty()
                        && $propertyRooms->every(fn (Room $room): bool => $this->availability->isRoomAvailable($room, $start, $end)['available']);

                    $capacity = max((int) $property->capacity, (int) ($houseRoom?->capacity ?? 0));

                    if ($houseRoom && $houseAvailable && $guests <= $capacity) {
                        $quote = $this->pricing->calculateForRange($houseRoom, $start, $end, $guests, null, true);
                        $houseRoom->setAttribute('quote', $quote);
                        $houseRoom->setAttribute('house_name', $property->name);
                        $houseRoom->setAttribute('house_capacity', $capacity);
                        $houseRoom->load(['property', 'images' => fn ($q) => $q->orderBy('sort_order')]);
                        $rooms = collect([$houseRoom]);
                    }
                }
            }
        }

        $property ??= $activeProperty;

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

    /**
     * Resolve the default whole-house listing for the booking search.
     *
     * The room marked as primary is preferred; without one, the first active
     * room is used. Preference stops at the first candidate whose entire
     * property is free for the requested dates and fits the guest count.
     * Returns null only when no active room exists or fits the requested stay,
     * so the caller falls back to the active property.
     */
    private function preferredSearchProperty(Carbon $start, Carbon $end, int $guests): ?Property
    {
        $candidateRooms = Room::query()
            ->with('property')
            ->where('status', 'active')
            ->whereHas('property', fn ($q) => $q->where('status', 'active'))
            ->orderByDesc('is_primary')
            ->orderBy('id')
            ->get();

        foreach ($candidateRooms as $room) {
            if (! $room->property) {
                continue;
            }

            $property = $room->property;

            $propertyRooms = Room::query()
                ->with('property')
                ->where('property_id', $property->id)
                ->where('status', 'active')
                ->get();

            $available = $propertyRooms->isNotEmpty()
                && $propertyRooms->every(fn (Room $propertyRoom): bool => $this->availability->isRoomAvailable($propertyRoom, $start, $end)['available'])
                && $guests <= max((int) $property->capacity, (int) $room->capacity);

            if ($available) {
                return $property;
            }
        }

        return null;
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

    /**
     * Accept a booking request from the website widget or the guest-details form.
     *
     * Direct bookings are enquiry-first: no payment is taken here. A 48-hour
     * hold is placed on the requested dates, the enquiry is stored against the
     * room and hold, and the booking team is emailed. Once the guest's photo ID
     * and signed rental agreement have been reviewed, the team emails a payment
     * link so a refundable £950 deposit can be taken online.
     */
    public function requestBooking(Request $request, MailConfigurationService $mailConfigurationService): RedirectResponse|JsonResponse
    {
        $request->merge([
            'name' => $request->input('name', trim(($request->input('guest_first_name') ?? 'Guest').' '.($request->input('guest_last_name') ?? ''))),
            'email' => $request->input('email', $request->input('guest_email')),
            'phone' => $request->input('phone', $request->input('guest_phone')),
            'guests' => (string) $request->input('guests', $request->input('guests_count', 12)),
            'check_in' => $request->input('check_in', $request->input('checkIn')),
            'check_out' => $request->input('check_out', $request->input('checkOut')),
            'room_id' => $request->input('room_id', $request->input('roomId')),
        ]);

        try {
            $data = $request->validate([
                'room_id' => ['required', 'exists:rooms,id'],
                'check_in' => ['required', 'date'],
                'check_out' => ['required', 'date', 'after:check_in'],
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email'],
                'phone' => ['nullable', 'string', 'max:60'],
                'guests' => ['nullable', 'string', 'max:60'],
                'message' => ['nullable', 'string', 'max:5000'],
                'drinks' => ['nullable', 'boolean'],
                'agree' => ['nullable', 'boolean'],
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
        $guestCount = (int) preg_replace('/[^0-9]/', '', (string) ($data['guests'] ?? '')) ?: 12;

        // 24-hour advance notice
        $minAdvanceDays = (int) Setting::getValue('min_advance_days', 1);
        if ($checkIn->lt(Carbon::today()->addDays($minAdvanceDays))) {
            return $this->requestFailure($request, 'Bookings require at least '.$minAdvanceDays.' days advance notice.');
        }

        // Max occupancy
        $maxAdults = (int) Setting::getValue('max_adults', 12);
        if ($guestCount > $maxAdults) {
            return $this->requestFailure($request, 'Maximum '.$maxAdults.' adults allowed.');
        }

        $quote = $this->pricing->calculateForRange($room, $checkIn, $checkOut, $guestCount, null, true);

        if ($checkIn->diffInDays($checkOut) < $quote['minimum_stay']) {
            return $this->requestFailure($request, 'This stay does not meet the '.$quote['minimum_stay'].'-night minimum.');
        }

        if (($quote['maximum_stay'] ?? null) !== null && $checkIn->diffInDays($checkOut) > $quote['maximum_stay']) {
            return $this->requestFailure($request, 'This stay exceeds the '.$quote['maximum_stay'].'-night maximum.');
        }

        // The refundable deposit (£950) is quoted now and collected later via a
        // payment link once the lead guest has been verified.
        $damageDeposit = (float) Setting::getValue('damage_deposit', 950);
        $quote['total'] = round($quote['total'] + $damageDeposit, 2);

        $addonIds = $data['addon_ids'] ?? [];
        $addons = AddOn::query()->whereIn('id', $addonIds)->where('is_active', true)->get();
        $addonsTotal = (float) $addons->sum('price');
        $quote['total'] = round($quote['total'] + $addonsTotal, 2);

        $message = trim((string) ($data['message'] ?? ''));
        if ($addons->isNotEmpty()) {
            $message = trim($message.' '.($message ? '- ' : '').'Add-ons requested: '.$addons->pluck('name')->join(', ').'.');
        }

        try {
            $hold = $this->holds->createHold(
                $room->id,
                $checkIn,
                $checkOut,
                $request->session()->getId(),
                $quote['total'],
                (int) Setting::getValue('booking_request_hold_hours', 48) * 60,
            );

            $enquiry = Enquiry::create([
                'type' => Enquiry::TYPE_BOOKING,
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'room_id' => $room->id,
                'guests' => $data['guests'] ?? (string) $guestCount,
                'check_in' => $checkIn->toDateString(),
                'check_out' => $checkOut->toDateString(),
                'nights' => $checkIn->diffInDays($checkOut),
                'message' => $message,
                'drinks_package' => (bool) ($data['drinks'] ?? false),
                'terms_accepted' => (bool) ($data['agree'] ?? false),
                'booking_hold_id' => $hold['hold']->id,
            ]);

            $this->sendBookingRequestMail($request, $mailConfigurationService, $enquiry, $room, $hold['expires_at'], $quote);

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'ok',
                    'enquiry_id' => $enquiry->id,
                    'redirect_url' => route('booking.requested', ['enquiry' => $enquiry->id]),
                ]);
            }

            return redirect()->route('booking.requested', ['enquiry' => $enquiry->id]);
        } catch (\DomainException $e) {
            return $this->requestFailure($request, $e->getMessage());
        } catch (Throwable $e) {
            Log::error('Booking request could not be stored', [
                'message' => $e->getMessage(),
            ]);

            return $this->requestFailure($request, 'Your booking request could not be submitted. Please try again.');
        }
    }

    /**
     * Resolve an expiring payment link to the guest payment page.
     *
     * A link is single-reservation: expiry is enforced server-side, so a stale
     * or revoked link never reaches the payment page.
     */
    public function payLink(Request $request, string $token): View|RedirectResponse
    {
        $paymentLink = PaymentLink::query()->with('reservation')->where('token', $token)->first();

        if (! $paymentLink || $paymentLink->isExpired()) {
            return view('website.booking.payment-link-expired');
        }

        return redirect()->route('booking.checkout', $paymentLink->reservation->getRouteKey());
    }

    public function requestReceived(Request $request): View
    {
        $enquiry = null;

        if ($request->filled('enquiry')) {
            $enquiry = Enquiry::query()->with('room')->find($request->query('enquiry'));
        }

        return view('website.booking.requested', ['enquiry' => $enquiry]);
    }

    private function requestFailure(Request $request, string $message): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['error' => $message], 422);
        }

        return back()->withInput()->withErrors(['error' => $message]);
    }

    /**
     * Notify the booking team about a new booking request. Best-effort: the
     * enquiry is already stored, so a failed mail must not block the request.
     */
    private function sendBookingRequestMail(
        Request $request,
        MailConfigurationService $mailConfigurationService,
        Enquiry $enquiry,
        Room $room,
        Carbon $expiresAt,
        array $quote,
    ): void {
        try {
            $mailConfigurationService->apply();

            $recipient = Setting::getValue('booking_notify_email', Setting::getValue('admin_notification_email', config('mail.from.address')));

            Mail::to($recipient)->send(new NewBookingRequestMail(
                $enquiry,
                $room,
                $expiresAt,
                (float) $quote['total'],
            ));
        } catch (Throwable $e) {
            Log::warning("Booking request email could not be sent for enquiry {$enquiry->id}.", [
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);
        }
    }

    public function checkoutPage(Request $request, Reservation $reservation): View|RedirectResponse
    {
        $validated = $request->validate([
            'payment_option' => ['sometimes', 'required', 'in:deposit,full'],
        ]);
        $paymentOption = $validated['payment_option'] ?? 'full';

        $reservation->load(['room.images', 'guest', 'property', 'addons', 'payments']);

        $latestPayment = $reservation->payments()->latest()->first();

        $outstandingAmount = max(0.0, round((float) $reservation->total_amount - (float) $reservation->paid_amount, 2));
        $isBalancePayment = (float) $reservation->paid_amount > 0;

        if ($reservation->payment_status === 'paid' || $outstandingAmount <= 0) {
            $request->session()->put('booking.reservation_id', $reservation->id);

            return redirect()->route('booking.confirmation', [
                'session_id' => $latestPayment?->provider_session_id ?? 'paid',
            ]);
        }

        $deposit = (float) Setting::getValue('damage_deposit', 950);
        $paymentAmount = $isBalancePayment || $paymentOption === 'full' ? $outstandingAmount : $deposit;
        $balanceDue = max(0.0, round($outstandingAmount - $paymentAmount, 2));

        // Build Stripe Checkout Session URL for the hosted option.
        $checkoutUrl = null;
        $checkoutPayment = $reservation->payments()
            ->where('provider', 'stripe')
            ->where('status', 'pending')
            ->where('amount', $paymentAmount)
            ->whereNotNull('provider_session_id')
            ->latest('id')
            ->first();
        if ($checkoutPayment && ! blank($checkoutPayment->metadata['checkout_url'] ?? null)) {
            $checkoutUrl = $this->payments->checkoutUrl($checkoutPayment);
        }

        if (! $checkoutUrl) {
            try {
                $payment = $this->payments->startCheckout(
                    $reservation,
                    route('booking.confirmation').'?session_id={CHECKOUT_SESSION_ID}',
                    route('booking.checkout', [$reservation->getRouteKey(), 'cancelled' => 1, 'payment_option' => $paymentOption]),
                    $paymentAmount,
                );
                $checkoutUrl = $this->payments->checkoutUrl($payment);
            } catch (Throwable $e) {
                Log::warning('Failed to generate Stripe checkout session', [
                    'message' => $e->getMessage(),
                ]);
            }
        }

        // Prepare a Payment Intent so guests can enter card details directly.
        $paymentIntentSecret = null;
        $paymentIntentId = null;
        try {
            $intentPayment = $this->payments->createIntent($reservation, $paymentAmount);
            $paymentIntentSecret = $this->payments->clientSecret($intentPayment);
            $paymentIntentId = $intentPayment->provider_payment_id;
        } catch (Throwable $e) {
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
            'deposit' => $deposit,
            'paymentOption' => $paymentOption,
            'paymentAmount' => $paymentAmount,
            'isBalancePayment' => $isBalancePayment,
            'balanceDue' => $balanceDue,
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
        } catch (Throwable $e) {
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
            } catch (Throwable) {
                $reservation = Reservation::query()
                    ->whereHas('payments', fn ($q) => $q->where('provider_session_id', $sessionId))
                    ->first();
            }
        }

        if (! $reservation && $intentId = $request->query('payment_intent')) {
            try {
                $payment = $this->payments->confirmFromIntent($intentId);
                $reservation = $payment->reservation;
            } catch (Throwable) {
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
            : Room::defaultForDirectBookings();

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
