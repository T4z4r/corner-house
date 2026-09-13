<?php

namespace App\Services\Availability;

use App\Models\BookingHold;
use App\Models\CalendarBlock;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Builder;
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

        // Linked properties are duplicate listings of the same accommodation,
        // so reservations, holds and blocks on the linked partner block this
        // room from sale as well (shared live inventory).
        $roomIds = $this->linkedRoomIds($room);
        $propertyIds = $this->linkedPropertyIds($room);

        $reservationOverlap = Reservation::query()
            ->active()
            ->whereIn('room_id', $roomIds)
            ->whereDate('check_in', '<', $checkOut)
            ->whereDate('check_out', '>', $checkIn)
            ->when($ignoredReservationIds, fn ($q) => $q->whereNotIn('id', $ignoredReservationIds))
            ->exists();

        if ($reservationOverlap) {
            $conflicts[] = 'Overlapping confirmed or pending reservation';
        }

        // Checkout day turnaround: a guest cannot check in on the date the
        // current guest departs, so the room is never turned around same-day.
        $turnaroundConflict = Reservation::query()
            ->active()
            ->whereIn('room_id', $roomIds)
            ->whereDate('check_out', $checkIn->toDateString())
            ->when($ignoredReservationIds, fn ($q) => $q->whereNotIn('id', $ignoredReservationIds))
            ->exists();

        if ($turnaroundConflict) {
            $conflicts[] = 'Check-in is blocked on the day the current guest departs (no same-day turnaround).';
        }

        $holdOverlap = BookingHold::query()
            ->active()
            ->whereIn('room_id', $roomIds)
            ->whereDate('check_in', '<', $checkOut)
            ->whereDate('check_out', '>', $checkIn)
            ->when($ignoredHoldIds, fn ($q) => $q->whereNotIn('id', $ignoredHoldIds))
            ->exists();

        if ($holdOverlap) {
            $conflicts[] = 'Overlapping active booking hold';
        }

        $blockOverlap = CalendarBlock::query()
            ->blockingInventory()
            ->where(function (Builder $q) use ($roomIds, $propertyIds) {
                $q->whereIn('room_id', $roomIds)
                    ->orWhere(fn ($q2) => $q2->whereNull('room_id')->whereIn('property_id', $propertyIds));
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

    /**
     * Room ids in the linked group: the room itself plus every room on the
     * linked partner property. Duplicate listings share one live inventory,
     * so any of these rooms being sold pulls the night off the market.
     *
     * @return array<int, int>
     */
    private function linkedRoomIds(Room $room): array
    {
        $ids = [$room->id];
        $property = $room->property;

        if ($property && $property->linked_property_id && (int) $property->linked_property_id !== (int) $room->property_id) {
            $ids = array_merge($ids, Room::query()
                ->where('property_id', (int) $property->linked_property_id)
                ->pluck('id')
                ->all());
        }

        return array_values(array_unique(array_map('intval', $ids)));
    }

    /**
     * Property ids in the linked group (this property plus its linked partner).
     *
     * @return array<int, int>
     */
    private function linkedPropertyIds(Room $room): array
    {
        return $room->property?->linkedPropertyIds() ?? [(int) $room->property_id];
    }

    /**
     * Blocked-night ranges for the website availability calendar.
     *
     * Each range is end-exclusive ({start, end} covering nights in [start, end))
     * and merges adjacent blocked nights. Sources are active reservations,
     * unexpired booking holds, inventory-blocking calendar blocks across every
     * room on the property (plus property-wide blocks) and the
     * website_blocked_dates setting. Historic nights are omitted.
     *
     * @return array<int, array{start: string, end: string}>
     */
    public function websiteBlockedRanges(?int $propertyId): array
    {
        $nights = [];

        $property = $propertyId ? Property::find($propertyId) : null;
        $propertyIds = $property ? $property->linkedPropertyIds() : ($propertyId ? [(int) $propertyId] : []);
        $roomIds = $propertyIds !== [] ? Room::query()->whereIn('property_id', $propertyIds)->pluck('id') : collect();

        if ($roomIds->isNotEmpty()) {
            Reservation::query()
                ->whereIn('room_id', $roomIds)
                ->active()
                ->get(['check_in', 'check_out'])
                ->each(function (Reservation $reservation) use (&$nights): void {
                    for ($date = $reservation->check_in->copy(); $date->lt($reservation->check_out); $date = $date->addDay()) {
                        $nights[$date->toDateString()] = true;
                    }
                });

            BookingHold::query()
                ->whereIn('room_id', $roomIds)
                ->active()
                ->get(['check_in', 'check_out'])
                ->each(function (BookingHold $hold) use (&$nights): void {
                    for ($date = $hold->check_in->copy(); $date->lt($hold->check_out); $date = $date->addDay()) {
                        $nights[$date->toDateString()] = true;
                    }
                });

            CalendarBlock::query()
                ->blockingInventory()
                ->where(function (Builder $query) use ($propertyIds, $roomIds): void {
                    $query->whereNull('room_id')->whereIn('property_id', $propertyIds)
                        ->orWhereIn('room_id', $roomIds);
                })
                ->get()
                ->each(function (CalendarBlock $block) use (&$nights): void {
                    for ($date = $block->start_date->copy(); $date->lte($block->end_date); $date = $date->addDay()) {
                        $nights[$date->toDateString()] = true;
                    }
                });
        }

        foreach ((array) Setting::getValue('website_blocked_dates', []) as $date) {
            if (! $date) {
                continue;
            }

            try {
                $nights[Carbon::parse($date)->toDateString()] = true;
            } catch (\Throwable) {
                // Ignore malformed dates in the setting.
            }
        }

        ksort($nights);

        $today = Carbon::today()->toDateString();
        $dates = array_values(array_filter(array_keys($nights), fn (string $date): bool => $date >= $today));

        return $this->collapseBlockedNights($dates);
    }

    /**
     * @param  array<int, string>  $dates
     * @return array<int, array{start: string, end: string}>
     */
    private function collapseBlockedNights(array $dates): array
    {
        $ranges = [];
        $start = null;
        $prev = null;

        foreach ($dates as $date) {
            if ($start === null) {
                $start = $date;
                $prev = $date;

                continue;
            }

            if (Carbon::parse($prev)->addDay()->toDateString() === $date) {
                $prev = $date;

                continue;
            }

            $ranges[] = ['start' => $start, 'end' => Carbon::parse($prev)->addDay()->toDateString()];
            $start = $date;
            $prev = $date;
        }

        if ($start !== null) {
            $ranges[] = ['start' => $start, 'end' => Carbon::parse($prev)->addDay()->toDateString()];
        }

        return $ranges;
    }
}
