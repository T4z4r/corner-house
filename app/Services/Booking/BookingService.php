<?php

namespace App\Services\Booking;

use App\Jobs\PushChannelAvailabilityJob;
use App\Jobs\SendBookingConfirmationJob;
use App\Models\BookingHold;
use App\Models\Guest;
use App\Models\Reservation;
use App\Models\Room;
use App\Services\Availability\AvailabilityService;
use App\Services\Pricing\PricingEngine;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BookingService
{
    public function __construct(
        private readonly AvailabilityService $availability,
        private readonly PricingEngine $pricing,
    ) {}

    /**
     * Create a confirmed/pending reservation with full double-booking protection.
     *
     * The room row is locked with lockForUpdate() to serialise concurrent
     * booking attempts for the same room. Availability is re-checked inside
     * the locked transaction so two simultaneous requests cannot both succeed.
     *
     * @param  array<string, mixed>  $data  see $data validation contract in BookingController
     * @return array{reservation: Reservation, total: float}
     */
    public function create(array $data): array
    {
        // Idempotency: if an external booking with the same channel+id already
        // exists, return it instead of creating a duplicate (used by webhooks).
        if (! empty($data['external_channel']) && ! empty($data['external_booking_id'])) {
            $existing = Reservation::query()
                ->where('external_channel', $data['external_channel'])
                ->where('external_booking_id', $data['external_booking_id'])
                ->first();

            if ($existing) {
                return ['reservation' => $existing, 'total' => $existing->total_amount];
            }
        }

        $checkIn = Carbon::parse($data['check_in']);
        $checkOut = Carbon::parse($data['check_out']);
        $room = Room::findOrFail($data['room_id']);
        $hold = ! empty($data['hold_token'])
            ? BookingHold::query()->where('hold_token', $data['hold_token'])->first()
            : null;

        if ($checkOut->lte($checkIn)) {
            throw new \DomainException('Check-out must be after check-in.');
        }

        $minimumStay = $this->pricing->minimumStayForRange($room, $checkIn, $checkOut);

        if ($checkIn->diffInDays($checkOut) < $minimumStay) {
            throw new \DomainException('Minimum stay not met.');
        }

        $maximumStay = $this->pricing->maximumStayForRange($room, $checkIn, $checkOut);
        if ($maximumStay !== null && $checkIn->diffInDays($checkOut) > $maximumStay) {
            throw new \DomainException('Maximum stay exceeded.');
        }

        $reservation = DB::transaction(function () use ($data, $room, $checkIn, $checkOut, $hold) {
            // Lock the room row: serialises concurrent bookings for this room.
            $lockedRoom = Room::query()
                ->whereKey($room->getKey())
                ->lockForUpdate()
                ->first();

            if (! $lockedRoom) {
                throw new \DomainException('Room not found.');
            }

            // Re-check availability inside the locked transaction.
            $this->availability->assertAvailable(
                $lockedRoom,
                $checkIn,
                $checkOut,
                ignoredHoldIds: $hold ? [$hold->id] : [],
            );

            // Calculate the final price server-side; never trust the browser.
            $price = $this->pricing->calculateForRange(
                $lockedRoom,
                $checkIn,
                $checkOut,
                $data['guests_count'] ?? 1,
            );

            $guest = $this->firstOrCreateGuest($data);

            $reservation = Reservation::create([
                'reference' => Reservation::generateReference(),
                'property_id' => $lockedRoom->property_id,
                'room_id' => $lockedRoom->id,
                'guest_id' => $guest?->id,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'guests_count' => $data['guests_count'] ?? 1,
                'status' => $data['status'] ?? 'pending',
                'source' => $data['source'] ?? 'direct',
                'channel' => $data['channel'] ?? null,
                'external_channel' => $data['external_channel'] ?? null,
                'external_booking_id' => $data['external_booking_id'] ?? null,
                'base_amount' => $price['base_amount'],
                'discount_amount' => $price['discount_amount'],
                'tax_amount' => $price['tax_amount'],
                'fees_amount' => $price['fees_amount'],
                'total_amount' => $price['total'],
                'paid_amount' => 0,
                'payment_status' => 'unpaid',
                'sync_status' => $data['skip_sync'] ?? false ? 'none' : 'pending',
                'notes' => $data['notes'] ?? null,
            ]);

            if ($hold && $hold->status === 'active') {
                $hold->update(['status' => 'converted']);
            }

            if (($data['status'] ?? '') === 'confirmed') {
                $reservation->forceFill(['confirmed_at' => now()])->save();
            }

            return $reservation;
        });

        if ($reservation->status === 'confirmed') {
            $this->afterConfirm($reservation);
        }

        return ['reservation' => $reservation, 'total' => $reservation->total_amount];
    }

    public function confirm(Reservation $reservation): Reservation
    {
        if ($reservation->status === 'confirmed') {
            return $reservation;
        }

        if (in_array($reservation->status, ['cancelled', 'no_show'], true)) {
            throw new \DomainException('A cancelled reservation cannot be confirmed.');
        }

        $reservation->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);

        $this->afterConfirm($reservation);

        return $reservation->fresh();
    }

    /**
     * Cancel a reservation (idempotent, with data preservation for audit).
     */
    public function cancel(Reservation $reservation, ?string $reason = null, ?int $cancelledBy = null): Reservation
    {
        if (in_array($reservation->status, ['cancelled', 'no_show'])) {
            return $reservation;
        }

        return DB::transaction(function () use ($reservation, $reason, $cancelledBy) {
            $reservation->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => ['reason' => $reason],
                'cancelled_by' => $cancelledBy,
            ]);

            PushChannelAvailabilityJob::dispatch($reservation->id);

            return $reservation;
        });
    }

    private function afterConfirm(Reservation $reservation): void
    {
        SendBookingConfirmationJob::dispatch($reservation->id);
        PushChannelAvailabilityJob::dispatch($reservation->id);
    }

    /**
     * Find or create a guest from booking data, if provided.
     */
    private function firstOrCreateGuest(array $data): ?Guest
    {
        if (! empty($data['guest_id'])) {
            return Guest::find($data['guest_id']);
        }

        if (empty($data['guest_email']) && empty($data['guest_first_name'])) {
            return null;
        }

        $email = $data['guest_email'] ?? null;

        $existing = $email ? Guest::where('email', $email)->first() : null;

        if ($existing) {
            return $existing;
        }

        return Guest::create([
            'first_name' => $data['guest_first_name'] ?? $data['lead_first_name'] ?? 'Guest',
            'last_name' => $data['guest_last_name'] ?? $data['lead_last_name'] ?? '',
            'email' => $email,
            'phone' => $data['guest_phone'] ?? null,
            'country' => $data['guest_country'] ?? null,
            'source' => $data['source'] ?? 'direct',
        ]);
    }
}
