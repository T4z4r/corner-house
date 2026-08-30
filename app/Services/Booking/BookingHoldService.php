<?php

namespace App\Services\Booking;

use App\Models\BookingHold;
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
}
