<?php

namespace App\Services\Booking;

use App\Models\BookingHold;
use App\Models\Enquiry;
use App\Models\Reservation;
use App\Models\Room;
use App\Services\Availability\AvailabilityService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BookingHoldService
{
    public function __construct(private readonly AvailabilityService $availability) {}

    /**
     * Create an active booking hold for a room/date range.
     *
     * The hold is created inside a transaction that locks the room row and
     * re-checks availability, preventing two simultaneous holds for the same
     * inventory.
     *
     * @return array{hold: BookingHold, expires_at: Carbon}
     */
    public function createHold(int $roomId, Carbon $checkIn, Carbon $checkOut, string $sessionId, float $quotedTotal, int $holdMinutes = 15): array
    {
        $room = Room::findOrFail($roomId);

        if ($checkOut->lte($checkIn)) {
            throw new \DomainException('Check-out must be after check-in.');
        }

        $hold = DB::transaction(function () use ($room, $checkIn, $checkOut, $sessionId, $quotedTotal, $holdMinutes): BookingHold {
            $lockedRoom = Room::query()->whereKey($room->getKey())->lockForUpdate()->first();

            $this->availability->assertAvailable($lockedRoom, $checkIn, $checkOut);

            return BookingHold::create([
                'property_id' => $lockedRoom->property_id,
                'room_id' => $lockedRoom->id,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'session_id' => $sessionId,
                'status' => 'active',
                'quoted_total' => $quotedTotal,
                'expires_at' => now()->addMinutes($holdMinutes),
            ]);
        });

        return ['hold' => $hold, 'expires_at' => $hold->expires_at];
    }

    /**
     * Release a hold (used on cancellation or explicit release).
     */
    public function release(BookingHold $hold): void
    {
        if ($hold->status === 'active') {
            $hold->update(['status' => 'released']);
        }
    }

    /**
     * Expire holds whose expiry has passed. Returns the number released.
     */
    public function expireExpiredHolds(): int
    {
        $count = 0;

        BookingHold::query()
            ->where('status', 'active')
            ->where('expires_at', '<=', now())
            ->each(function (BookingHold $hold) use (&$count): void {
                $hold->update(['status' => 'released']);
                $count++;
            });

        return $count;
    }

    /**
     * Save direct bookings that were abandoned at checkout as Enquiries.
     *
     * A direct booking becomes `hold`-status, `unpaid` when the guest reaches
     * Stripe Checkout. If they abandon it, the linked hold eventually expires
     * and the reservation never converts to paid. Each such reservation is
     * copied into an Enquiry (keeping the reservation and linking to it) so
     * the team can follow it up. Returns the number of enquiries created.
     */
    public function saveAbandonedDirectBookingsAsEnquiries(): int
    {
        $count = 0;

        Reservation::query()
            ->with(['guest', 'bookingHold'])
            ->where('source', 'direct')
            ->where('status', 'hold')
            ->where('payment_status', 'unpaid')
            ->whereNull('cancelled_at')
            ->whereNotNull('booking_hold_id')
            ->whereHas('bookingHold', fn ($query) => $query->where('expires_at', '<=', now()))
            ->whereDoesntHave('enquiry')
            ->each(function (Reservation $reservation) use (&$count): void {
                $guest = $reservation->guest;

                Enquiry::create([
                    'type' => Enquiry::TYPE_BOOKING,
                    'name' => $guest?->full_name ?? 'Website guest',
                    'email' => $guest?->email ?? '',
                    'phone' => $guest?->phone,
                    'guests' => (string) $reservation->guests_count,
                    'check_in' => $reservation->check_in->toDateString(),
                    'check_out' => $reservation->check_out->toDateString(),
                    'nights' => $reservation->check_in->diffInDays($reservation->check_out),
                    'message' => 'Abandoned direct booking (reference '.$reservation->reference.'). The guest did not complete checkout.',
                    'drinks_package' => $reservation->drinks_package,
                    'terms_accepted' => true,
                    'reservation_id' => $reservation->id,
                ]);

                $count++;
            });

        return $count;
    }
}
