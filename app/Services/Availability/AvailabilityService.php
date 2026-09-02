<?php

namespace App\Services\Availability;

use App\Models\BookingHold;
use App\Models\CalendarBlock;
use App\Models\Reservation;
use App\Models\Room;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AvailabilityService
{
    /**
     * Check whether a room is available for a given date range.
     *
     * @return array{available: bool, conflicts: array<int, string>}
     */
    public function isRoomAvailable(Room $room, Carbon $checkIn, Carbon $checkOut, array $ignoredReservationIds = [], array $ignoredHoldIds = []): array
    {
        $conflicts = [];

        if (! $room->isActive()) {
            $conflicts[] = 'Room is not available';

            return ['available' => false, 'conflicts' => $conflicts];
        }

        $reservationOverlap = Reservation::query()
            ->active()
            ->overlapsDates($room->id, $checkIn, $checkOut)
            ->when($ignoredReservationIds, fn ($q) => $q->whereNotIn('id', $ignoredReservationIds))
            ->exists();

        if ($reservationOverlap) {
            $conflicts[] = 'Overlapping confirmed or pending reservation';
        }

        $holdOverlap = BookingHold::query()
            ->active()
            ->overlapsDates($room->id, $checkIn, $checkOut)
            ->when($ignoredHoldIds, fn ($q) => $q->whereNotIn('id', $ignoredHoldIds))
            ->exists();

        if ($holdOverlap) {
            $conflicts[] = 'Overlapping active booking hold';
        }

        $blockOverlap = CalendarBlock::query()
            ->blockingInventory()
            ->where(function ($q) use ($room) {
                $q->where('room_id', $room->id)
                    ->orWhere(fn ($q2) => $q2->whereNull('room_id')->where('property_id', $room->property_id));
            })
            ->whereDate('start_date', '<=', $checkOut->toDateString())
            ->whereDate('end_date', '>=', $checkIn->toDateString())
            ->exists();

        if ($blockOverlap) {
            $conflicts[] = 'Room is blocked for this period';
        }

        return ['available' => empty($conflicts), 'conflicts' => $conflicts];
    }

    /**
     * List rooms available for a date range on a property.
     *
     * @return Collection<int, Room>
     */
    public function listAvailableRooms(int $propertyId, Carbon $checkIn, Carbon $checkOut, int $guests = 1): Collection
    {
        return Room::query()
            ->where('property_id', $propertyId)
            ->where('status', 'active')
            ->where('capacity', '>=', $guests)
            ->get()
            ->filter(fn (Room $room) => $this->isRoomAvailable($room, $checkIn, $checkOut)['available'])
            ->values();
    }

    /**
     * Assert a room is available or throw a business exception.
     */
    public function assertAvailable(Room $room, Carbon $checkIn, Carbon $checkOut, array $ignoredReservationIds = [], array $ignoredHoldIds = []): void
    {
        $result = $this->isRoomAvailable($room, $checkIn, $checkOut, $ignoredReservationIds, $ignoredHoldIds);

        if (! $result['available']) {
            throw new \DomainException('Room unavailable: '.implode('; ', $result['conflicts']));
        }
    }
}
