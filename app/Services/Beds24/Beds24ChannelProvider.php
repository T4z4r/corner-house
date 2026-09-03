<?php

namespace App\Services\Beds24;

use App\Models\ChannelAccount;
use App\Models\ChannelMapping;
use App\Services\Channel\ChannelProviderInterface;

class Beds24ChannelProvider implements ChannelProviderInterface
{
    public function __construct(private readonly Beds24Client $client) {}

    public function provider(): string
    {
        return 'beds24';
    }

    public function syncBookings(ChannelAccount $account): array
    {
        return $this->fetchBookings($account, $account->last_synced_at
            ? ['modifiedFrom' => $account->last_synced_at->toIso8601String()]
            : []);
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function fetchBookings(ChannelAccount $account, array $params = []): array
    {
        return $this->client->get($account, 'bookings', $params);
    }

    /**
     * @return array<string, mixed>
     */
    public function fetchProperties(ChannelAccount $account): array
    {
        return $this->client->get($account, 'properties', ['includeAllRooms' => 'true']);
    }

    /**
     * @return array<string, mixed>
     */
    public function fetchRooms(ChannelAccount $account, array $params = []): array
    {
        return $this->client->get($account, 'properties/rooms', $params);
    }

    /**
     * @return array<string, mixed>
     */
    public function fetchCalendar(ChannelAccount $account, string $roomId, string $from, string $to): array
    {
        return $this->client->get($account, 'inventory/rooms/calendar', [
            'roomId' => $roomId,
            'startDate' => $from,
            'endDate' => $to,
            'includeNumAvail' => 'true',
            'includeMinStay' => 'true',
            'includeMaxStay' => 'true',
            'includeMultiplier' => 'true',
            'includeOverride' => 'true',
            'includePrices' => 'true',
            'includeChannels' => 'true',
        ]);
    }

    public function syncAvailability(ChannelAccount $account): array
    {
        return $this->client->get($account, 'inventory/rooms/availability');
    }

    public function pushAvailability(ChannelAccount $account, array $availability): bool
    {
        $this->client->post($account, 'inventory/rooms/calendar', $this->calendarPayload($availability));

        return true;
    }

    public function pushRates(ChannelAccount $account, array $rates): bool
    {
        $this->client->post($account, 'inventory/rooms/calendar', $this->calendarPayload($rates));

        return true;
    }

    public function updateRestrictions(ChannelAccount $account, array $restrictions): bool
    {
        $this->client->post($account, 'inventory/rooms/calendar', $this->calendarPayload($restrictions));

        return true;
    }

    public function sendMessage(ChannelAccount $account, array $message): bool
    {
        $this->client->post($account, 'bookings/messages', $message);

        return true;
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<int, array<string, mixed>>
     */
    public function fetchMessages(ChannelAccount $account, array $params = []): array
    {
        $payload = $this->client->get($account, 'bookings/messages', $params);

        return $this->messageList($payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, array<string, mixed>>
     */
    private function messageList(array $payload): array
    {
        if (isset($payload['body']) && is_array($payload['body'])) {
            $payload = $payload['body'];
        }

        $messages = $payload['data'] ?? $payload['bookings'] ?? $payload['messages'] ?? $payload;

        if (isset($messages['id'], $messages['message'])) {
            return [$messages];
        }

        if (! is_array($messages)) {
            return [];
        }

        return array_values(array_filter($messages, 'is_array'));
    }

    /**
     * @return array<int, ChannelMapping>
     */
    public function importPropertyRooms(ChannelAccount $account, int $propertyId): array
    {
        $payload = $this->client->get($account, 'properties', ['includeAllRooms' => 'true']);
        $properties = $payload['data'] ?? $payload['properties'] ?? $payload;
        $created = [];

        if (! is_array($properties)) {
            return $created;
        }

        foreach ($properties as $property) {
            if (! is_array($property)) {
                continue;
            }

            $externalPropertyId = (string) ($property['id'] ?? '');
            $rooms = $property['roomTypes'] ?? $property['rooms'] ?? [];

            if (! is_array($rooms)) {
                continue;
            }

            foreach ($rooms as $room) {
                if (! is_array($room) || empty($room['id'])) {
                    continue;
                }

                $mapping = ChannelMapping::query()->updateOrCreate(
                    [
                        'channel_account_id' => $account->id,
                        'provider' => 'beds24',
                        'external_room_id' => (string) $room['id'],
                    ],
                    [
                        'property_id' => $propertyId,
                        'external_property_id' => $externalPropertyId,
                        'external_listing_id' => (string) ($room['name'] ?? ''),
                        'status' => 'inactive',
                        'metadata' => [
                            'beds24_property_name' => $property['name'] ?? null,
                            'beds24_room_name' => $room['name'] ?? null,
                        ],
                    ],
                );

                $created[] = $mapping;
            }
        }

        return $created;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function calendarPayload(array $rows): array
    {
        if ($rows === []) {
            return [];
        }

        if (isset($rows[0]['calendar'])) {
            return $rows;
        }

        $grouped = [];

        foreach ($rows as $row) {
            $roomId = $row['roomId'] ?? null;
            if ($roomId === null) {
                continue;
            }

            $grouped[(string) $roomId][] = array_filter([
                'from' => $row['from'] ?? null,
                'to' => $row['to'] ?? null,
                'numAvail' => $row['numAvail'] ?? $row['available'] ?? null,
                'price1' => $row['price1'] ?? $row['price'] ?? null,
                'minStay' => $row['minStay'] ?? $row['minimumStay'] ?? null,
                'maxStay' => $row['maxStay'] ?? $row['maximumStay'] ?? null,
                'multiplier' => $row['multiplier'] ?? null,
                'override' => $row['override'] ?? null,
            ], fn ($value) => $value !== null);
        }

        $payload = [];

        foreach ($grouped as $roomId => $calendar) {
            $payload[] = [
                'roomId' => is_numeric($roomId) ? (int) $roomId : $roomId,
                'calendar' => $calendar,
            ];
        }

        return $payload;
    }
}
