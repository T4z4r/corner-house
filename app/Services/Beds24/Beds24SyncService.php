<?php

namespace App\Services\Beds24;

use App\Models\CalendarBlock;
use App\Models\ChannelAccount;
use App\Models\ChannelMapping;
use App\Models\PricingOverride;
use App\Models\Property;
use App\Models\Room;
use App\Services\Channel\ChannelManager;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class Beds24SyncService
{
    public function __construct(
        private readonly Beds24ChannelProvider $provider,
        private readonly ChannelManager $channels,
    ) {}

    /**
     * @return array{properties: int, rooms: int, bookings: int, overrides: int, blocks: int, errors: string[]}
     */
    public function synchronize(ChannelAccount $account): array
    {
        $errors = [];

        try {
            $catalog = $this->syncCatalog($account);
        } catch (\Throwable $e) {
            Log::error('Beds24 catalog sync failed', ['account_id' => $account->id, 'message' => $e->getMessage()]);
            $errors[] = 'catalog: '.$e->getMessage();
            $catalog = ['properties' => 0, 'rooms' => 0];
        }

        try {
            $bookings = $this->channels->syncBookings($account, true);
        } catch (\Throwable $e) {
            Log::error('Beds24 bookings sync failed', ['account_id' => $account->id, 'message' => $e->getMessage()]);
            $errors[] = 'bookings: '.$e->getMessage();
            $bookings = 0;
        }

        try {
            $calendar = $this->syncCalendar($account);
        } catch (\Throwable $e) {
            Log::error('Beds24 calendar sync failed', ['account_id' => $account->id, 'message' => $e->getMessage()]);
            $errors[] = 'calendar: '.$e->getMessage();
            $calendar = ['overrides' => 0, 'blocks' => 0];
        }

        $account->update([
            'last_synced_at' => now(),
            'last_error' => $errors !== [] ? implode('; ', $errors) : null,
            'status' => $errors !== [] ? 'partial' : 'active',
            'settings' => array_merge($account->settings ?? [], [
                'last_full_sync_at' => now()->toIso8601String(),
                'last_sync_counts' => [
                    'properties' => $catalog['properties'],
                    'rooms' => $catalog['rooms'],
                    'bookings' => $bookings,
                    'overrides' => $calendar['overrides'],
                    'blocks' => $calendar['blocks'],
                ],
            ]),
        ]);

        return [
            'properties' => $catalog['properties'],
            'rooms' => $catalog['rooms'],
            'bookings' => $bookings,
            'overrides' => $calendar['overrides'],
            'blocks' => $calendar['blocks'],
            'errors' => $errors,
        ];
    }

    /**
     * @return array{properties: int, rooms: int}
     */
    public function syncCatalog(ChannelAccount $account): array
    {
        $payload = $this->provider->fetchProperties($account);
        $properties = $payload['data'] ?? $payload['properties'] ?? $payload;
        $propertyCount = 0;
        $roomCount = 0;

        if (! is_array($properties)) {
            return ['properties' => 0, 'rooms' => 0];
        }

        foreach ($properties as $remote) {
            if (! is_array($remote) || empty($remote['id'])) {
                continue;
            }

            try {
                $property = $this->upsertProperty($account, $remote);
                $propertyCount++;
            } catch (\Throwable $e) {
                Log::warning('Beds24 catalog: failed to sync property', [
                    'external_id' => $remote['id'] ?? null,
                    'message' => $e->getMessage(),
                ]);

                continue;
            }

            $rooms = $remote['roomTypes'] ?? $remote['rooms'] ?? [];
            if (! is_array($rooms)) {
                continue;
            }

            foreach ($rooms as $remoteRoom) {
                if (! is_array($remoteRoom) || empty($remoteRoom['id'])) {
                    continue;
                }

                try {
                    $this->upsertRoom($account, $property, (string) $remote['id'], $remote, $remoteRoom);
                    $roomCount++;
                } catch (\Throwable $e) {
                    Log::warning('Beds24 catalog: failed to sync room', [
                        'external_room_id' => $remoteRoom['id'] ?? null,
                        'message' => $e->getMessage(),
                    ]);
                }
            }
        }

        return ['properties' => $propertyCount, 'rooms' => $roomCount];
    }

    /**
     * @return array{rooms: int, skipped: int}
     */
    public function syncRooms(ChannelAccount $account): array
    {
        $propertyIds = ChannelMapping::query()
            ->where('channel_account_id', $account->id)
            ->where('provider', 'beds24')
            ->whereNotNull('property_id')
            ->whereNotNull('external_property_id')
            ->pluck('external_property_id')
            ->map(static fn (string $propertyId): int => (int) $propertyId)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($propertyIds === []) {
            return ['rooms' => 0, 'skipped' => 0];
        }

        try {
            $payload = $this->provider->fetchRooms($account, [
                'propertyId' => $propertyIds,
                'includeLanguages' => ['en'],
                'includeTexts' => ['property', 'roomType'],
                'includeUnitDetails' => true,
                'includeOffers' => true,
                'includePriceRules' => true,
                'includeUpsellItems' => true,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Beds24 properties/rooms sync failed, falling back to properties payload', [
                'account_id' => $account->id,
                'message' => $e->getMessage(),
            ]);

            $catalog = $this->syncCatalog($account);

            return [
                'rooms' => $catalog['rooms'],
                'skipped' => 0,
            ];
        }

        $rooms = $payload['data'] ?? $payload['rooms'] ?? $payload;
        $roomCount = 0;
        $skipped = 0;

        if (! is_array($rooms)) {
            return ['rooms' => 0, 'skipped' => 0];
        }

        foreach ($rooms as $remoteRoom) {
            if (! is_array($remoteRoom) || empty($remoteRoom['id']) || empty($remoteRoom['propertyId'])) {
                $skipped++;

                continue;
            }

            $property = $this->propertyForExternalId($account, (string) $remoteRoom['propertyId']);
            if (! $property instanceof Property) {
                $skipped++;

                continue;
            }

            try {
                $this->upsertRoomFromRoomPayload($account, $property, (string) $remoteRoom['propertyId'], $remoteRoom);
                $roomCount++;
            } catch (\Throwable $e) {
                $skipped++;
                Log::warning('Beds24 rooms sync failed for room', [
                    'external_room_id' => $remoteRoom['id'] ?? null,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return ['rooms' => $roomCount, 'skipped' => $skipped];
    }

    /**
     * @return array{overrides: int, blocks: int}
     */
    public function syncCalendar(ChannelAccount $account): array
    {
        $from = Carbon::today();
        $to = $from->copy()->addDays(90);
        $overrides = 0;
        $blocks = 0;

        $mappings = ChannelMapping::query()
            ->where('channel_account_id', $account->id)
            ->where('provider', 'beds24')
            ->whereNotNull('room_id')
            ->whereNotNull('external_room_id')
            ->with('room')
            ->get();

        foreach ($mappings as $mapping) {
            $room = $mapping->room;
            if (! $room instanceof Room) {
                continue;
            }

            try {
                $payload = $this->provider->fetchCalendar(
                    $account,
                    (string) $mapping->external_room_id,
                    $from->toDateString(),
                    $to->toDateString(),
                );
            } catch (\Throwable $e) {
                Log::warning('Beds24 calendar sync failed for room', [
                    'room_id' => $room->id,
                    'message' => $e->getMessage(),
                ]);

                continue;
            }

            try {
                $result = $this->applyCalendar($room, $this->calendarDays($payload, (string) $mapping->external_room_id), $from, $to);
                $overrides += $result['overrides'];
                $blocks += $result['blocks'];
            } catch (\Throwable $e) {
                Log::warning('Beds24 calendar apply failed for room', [
                    'room_id' => $room->id,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return ['overrides' => $overrides, 'blocks' => $blocks];
    }

    /**
     * @param  array<string, mixed>  $remote
     */
    private function upsertProperty(ChannelAccount $account, array $remote): Property
    {
        $externalId = (string) $remote['id'];
        $mapping = ChannelMapping::query()
            ->where('channel_account_id', $account->id)
            ->where('external_property_id', $externalId)
            ->whereNotNull('property_id')
            ->first();

        $name = (string) ($remote['name'] ?? $remote['title'] ?? 'Beds24 property '.$externalId);
        $attributes = [
            'name' => $name,
            'address_line_1' => $remote['address'] ?? $remote['address1'] ?? $remote['street'] ?? null,
            'city' => $remote['city'] ?? null,
            'postcode' => $remote['postcode'] ?? $remote['zip'] ?? null,
            'country' => $this->countryCode($remote['country'] ?? 'GB'),
            'latitude' => $remote['latitude'] ?? $remote['lat'] ?? null,
            'longitude' => $remote['longitude'] ?? $remote['lng'] ?? null,
            'currency' => $remote['currency'] ?? 'GBP',
            'status' => 'active',
        ];

        if ($mapping?->property_id) {
            $property = Property::query()->findOrFail($mapping->property_id);
            $property->update($attributes);
        } else {
            $property = Property::create([
                ...$attributes,
                'slug' => $this->uniquePropertySlug($name, $externalId),
            ]);
        }

        ChannelMapping::query()->updateOrCreate(
            [
                'channel_account_id' => $account->id,
                'provider' => 'beds24',
                'external_property_id' => $externalId,
                'external_room_id' => null,
            ],
            [
                'property_id' => $property->id,
                'status' => 'active',
                'metadata' => ['beds24_property_name' => $name],
            ],
        );

        return $property;
    }

    private function propertyForExternalId(ChannelAccount $account, string $externalPropertyId): ?Property
    {
        $mapping = ChannelMapping::query()
            ->where('channel_account_id', $account->id)
            ->where('provider', 'beds24')
            ->where('external_property_id', $externalPropertyId)
            ->whereNotNull('property_id')
            ->first();

        if (! $mapping?->property_id) {
            return null;
        }

        return Property::query()->find($mapping->property_id);
    }

    /**
     * @param  array<string, mixed>  $remoteProperty
     * @param  array<string, mixed>  $remoteRoom
     */
    private function upsertRoom(ChannelAccount $account, Property $property, string $externalPropertyId, array $remoteProperty, array $remoteRoom): Room
    {
        $externalRoomId = (string) $remoteRoom['id'];
        $mapping = ChannelMapping::query()
            ->where('channel_account_id', $account->id)
            ->where('external_room_id', $externalRoomId)
            ->first();

        $name = (string) ($remoteRoom['name'] ?? 'Room '.$externalRoomId);
        $capacity = (int) ($remoteRoom['maxPeople'] ?? $remoteRoom['qty'] ?? $remoteRoom['sleeps'] ?? 2);
        $attributes = [
            'property_id' => $property->id,
            'name' => $name,
            'description' => $remoteRoom['description'] ?? $remoteRoom['unitDescription'] ?? null,
            'type' => $remoteRoom['roomType'] ?? $remoteRoom['type'] ?? null,
            'capacity' => max(1, $capacity),
            'sleeps' => max(1, $capacity),
            'min_stay' => max(1, (int) ($remoteRoom['minStay'] ?? 1)),
            'status' => 'active',
        ];

        if ($mapping?->room_id) {
            $room = Room::query()->findOrFail($mapping->room_id);
            $room->update($attributes);
        } else {
            $room = Room::create([
                ...$attributes,
                'slug' => Str::slug($name).'-'.$externalRoomId,
                'base_rate' => 0,
            ]);
        }

        ChannelMapping::query()->updateOrCreate(
            [
                'channel_account_id' => $account->id,
                'provider' => 'beds24',
                'external_room_id' => $externalRoomId,
            ],
            [
                'property_id' => $property->id,
                'room_id' => $room->id,
                'external_property_id' => $externalPropertyId,
                'external_listing_id' => $name,
                'status' => 'active',
                'metadata' => [
                    'beds24_property_name' => $remoteProperty['name'] ?? $remoteProperty['title'] ?? null,
                    'beds24_room_name' => $name,
                ],
            ],
        );

        return $room;
    }

    /**
     * @param  array<string, mixed>  $remoteRoom
     */
    private function upsertRoomFromRoomPayload(ChannelAccount $account, Property $property, string $externalPropertyId, array $remoteRoom): Room
    {
        $externalRoomId = (string) $remoteRoom['id'];
        $mapping = ChannelMapping::query()
            ->where('channel_account_id', $account->id)
            ->where('provider', 'beds24')
            ->where('external_room_id', $externalRoomId)
            ->first();

        $texts = is_array($remoteRoom['texts'] ?? null) ? $remoteRoom['texts'] : [];
        $firstText = is_array($texts[0] ?? null) ? $texts[0] : [];
        $name = (string) ($remoteRoom['name'] ?? $firstText['displayName'] ?? 'Room '.$externalRoomId);
        $capacity = (int) ($remoteRoom['maxPeople'] ?? $remoteRoom['qty'] ?? 1);
        $attributes = [
            'property_id' => $property->id,
            'name' => $name,
            'description' => $firstText['roomDescription'] ?? $firstText['contentDescription'] ?? null,
            'type' => $remoteRoom['roomType'] ?? null,
            'capacity' => max(1, $capacity),
            'sleeps' => max(1, $capacity),
            'min_stay' => max(1, (int) ($remoteRoom['minStay'] ?? 1)),
            'max_stay' => isset($remoteRoom['maxStay']) ? (int) $remoteRoom['maxStay'] : null,
            'base_rate' => $remoteRoom['minPrice'] ?? $remoteRoom['rackRate'] ?? 0,
            'status' => 'active',
        ];

        if ($mapping?->room_id) {
            $room = Room::query()->findOrFail($mapping->room_id);
            $room->update($attributes);
        } else {
            $room = Room::create([
                ...$attributes,
                'slug' => $this->uniqueRoomSlug($name, $externalRoomId),
            ]);
        }

        ChannelMapping::query()->updateOrCreate(
            [
                'channel_account_id' => $account->id,
                'provider' => 'beds24',
                'external_room_id' => $externalRoomId,
            ],
            [
                'property_id' => $property->id,
                'room_id' => $room->id,
                'external_property_id' => $externalPropertyId,
                'external_listing_id' => $name,
                'status' => 'active',
                'metadata' => [
                    'beds24_room_name' => $name,
                    'beds24_room_type' => $remoteRoom['roomType'] ?? null,
                    'beds24_unit_count' => $remoteRoom['qty'] ?? null,
                ],
            ],
        );

        return $room;
    }

    /**
     * @param  array<string, array{date: string, numAvail: ?int, price: ?float, minStay: ?int, maxStay: ?int, multiplier: ?float, override: ?string}>  $days
     * @return array{overrides: int, blocks: int}
     */
    private function applyCalendar(Room $room, array $days, Carbon $from, Carbon $to): array
    {
        $overrides = 0;
        $closed = [];

        foreach ($days as $day) {
            if ($this->isClosedCalendarDay($day)) {
                $closed[] = $day['date'];
            }

            if (isset($day['price']) && (float) $day['price'] > 0) {
                PricingOverride::query()->updateOrCreate(
                    [
                        'room_id' => $room->id,
                        'start_date' => $day['date'],
                        'end_date' => $day['date'],
                        'notes' => 'beds24-sync',
                    ],
                    [
                        'rate' => $day['price'],
                        'minimum_stay' => $day['minStay'],
                        'is_enabled' => true,
                    ],
                );
                $overrides++;

                if ((float) $room->base_rate <= 0) {
                    $room->update(['base_rate' => $day['price']]);
                }
            }
        }

        CalendarBlock::query()
            ->where('room_id', $room->id)
            ->where('type', 'channel')
            ->whereDate('start_date', '<=', $to->toDateString())
            ->whereDate('end_date', '>=', $from->toDateString())
            ->delete();

        $blocks = 0;
        foreach ($this->collapseDates($closed) as $range) {
            CalendarBlock::create([
                'property_id' => $room->property_id,
                'room_id' => $room->id,
                'start_date' => $range['start'],
                'end_date' => $range['end'],
                'type' => 'channel',
                'title' => 'Beds24 closed',
                'notes' => 'beds24-sync',
            ]);
            $blocks++;
        }

        return ['overrides' => $overrides, 'blocks' => $blocks];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, array{date: string, numAvail: ?int, price: ?float, minStay: ?int, maxStay: ?int, multiplier: ?float, override: ?string}>
     */
    private function calendarDays(array $payload, string $roomId): array
    {
        $rows = $payload['data'] ?? $payload['calendar'] ?? $payload;
        $days = [];

        if (! is_array($rows)) {
            return $days;
        }

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            if (isset($row['calendar']) && is_array($row['calendar'])) {
                if (isset($row['roomId']) && (string) $row['roomId'] !== $roomId) {
                    continue;
                }
                foreach ($row['calendar'] as $entry) {
                    if (is_array($entry)) {
                        $this->appendCalendarEntry($days, $entry);
                    }
                }

                continue;
            }

            $this->appendCalendarEntry($days, $row);
        }

        ksort($days);

        return $days;
    }

    /**
     * @param  array<string, array{date: string, numAvail: ?int, price: ?float, minStay: ?int, maxStay: ?int, multiplier: ?float, override: ?string}>  $days
     * @param  array<string, mixed>  $entry
     */
    private function appendCalendarEntry(array &$days, array $entry): void
    {
        $start = $entry['date'] ?? $entry['from'] ?? null;
        $end = $entry['date'] ?? $entry['to'] ?? $start;

        if (! $start) {
            return;
        }

        $cursor = Carbon::parse($start)->startOfDay();
        $last = Carbon::parse((string) $end)->startOfDay();
        $price = $this->calendarPrice($entry);
        $numAvail = $entry['numAvail'] ?? $entry['available'] ?? null;
        $minStay = $entry['minStay'] ?? $entry['minimumStay'] ?? null;
        $maxStay = $entry['maxStay'] ?? $entry['maximumStay'] ?? null;
        $multiplier = $entry['multiplier'] ?? null;
        $override = isset($entry['override']) ? (string) $entry['override'] : null;

        while ($cursor->lte($last)) {
            $key = $cursor->toDateString();
            $days[$key] = [
                'date' => $key,
                'numAvail' => $numAvail !== null ? (int) $numAvail : null,
                'price' => $price !== null ? (float) $price : null,
                'minStay' => $minStay !== null ? (int) $minStay : null,
                'maxStay' => $maxStay !== null ? (int) $maxStay : null,
                'multiplier' => $multiplier !== null ? (float) $multiplier : null,
                'override' => $override,
            ];
            $cursor->addDay();
        }
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    private function calendarPrice(array $entry): ?float
    {
        foreach (range(1, 16) as $index) {
            $key = 'price'.$index;
            if (array_key_exists($key, $entry) && $entry[$key] !== null) {
                return (float) $entry[$key];
            }
        }

        if (array_key_exists('price', $entry) && $entry['price'] !== null) {
            return (float) $entry['price'];
        }

        return null;
    }

    /**
     * @param  array{date: string, numAvail: ?int, price: ?float, minStay: ?int, maxStay: ?int, multiplier: ?float, override: ?string}  $day
     */
    private function isClosedCalendarDay(array $day): bool
    {
        if (array_key_exists('numAvail', $day) && $day['numAvail'] !== null && (int) $day['numAvail'] <= 0) {
            return true;
        }

        $override = strtolower((string) ($day['override'] ?? ''));

        return $override !== '' && ($override === 'blackout' || $override === 'closed' || str_contains($override, 'blackout'));
    }

    /**
     * @param  array<int, string>  $dates
     * @return array<int, array{start: string, end: string}>
     */
    private function collapseDates(array $dates): array
    {
        $dates = array_values(array_unique($dates));
        sort($dates);
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

            $ranges[] = ['start' => $start, 'end' => $prev];
            $start = $date;
            $prev = $date;
        }

        if ($start !== null) {
            $ranges[] = ['start' => $start, 'end' => $prev];
        }

        return $ranges;
    }

    private function uniquePropertySlug(string $name, string $externalId): string
    {
        $slug = Str::slug($name) ?: 'property';
        if (! Property::query()->where('slug', $slug)->exists()) {
            return $slug;
        }

        return $slug.'-'.$externalId;
    }

    private function uniqueRoomSlug(string $name, string $externalId): string
    {
        $slug = Str::slug($name) ?: 'room';
        if (! Room::query()->where('slug', $slug)->exists()) {
            return $slug;
        }

        return $slug.'-'.$externalId;
    }

    private function countryCode(mixed $country): string
    {
        $value = strtoupper((string) $country);

        return strlen($value) === 2 ? $value : 'GB';
    }
}
