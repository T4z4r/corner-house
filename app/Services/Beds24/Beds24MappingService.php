<?php

namespace App\Services\Beds24;

use App\Models\ChannelAccount;
use App\Models\ChannelRateMap;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Beds24MappingService
{
    /** @var list<string> */
    private const BOOKING_COM_CHANNEL_NAMES = ['booking.com', 'bookingcom', 'booking'];

    public function __construct(private readonly Beds24Client $client) {}

    /**
     * Fetch and store the Booking.com mapping for a property, replacing any previous copy.
     *
     * The mapping is read from the authenticated API v2 /channels endpoint. The
     * legacy getmapping.php feed is avoided because it only answers to a browser
     * signed into Beds24 and returns "error" to server-side clients.
     */
    public function sync(ChannelAccount $account, string $propertyId): ChannelRateMap
    {
        $connection = $this->bookingComConnection($this->fetchChannels($account, $propertyId), $propertyId);

        return DB::transaction(function () use ($account, $propertyId, $connection) {
            $map = ChannelRateMap::query()->updateOrCreate(
                [
                    'channel_account_id' => $account->id,
                    'external_property_id' => $propertyId,
                ],
                ['synced_at' => now()],
            );

            $map->rates()->delete();

            foreach ($connection['mappings'] as $mapping) {
                $map->rates()->create([
                    'external_room_id' => $this->mappingValue($mapping, 'externalRoomId'),
                    'room_name' => null,
                    'external_rate_id' => $this->mappingValue($mapping, 'externalRateId'),
                    'rate_name' => '',
                    'policy' => null,
                    'policy_id' => null,
                    'max_persons' => null,
                    'fixed_occupancy' => null,
                    'is_child_rate' => false,
                    'parent_rate_id' => null,
                    'follows_price' => null,
                    'percentage' => null,
                    'pricing_type' => null,
                    'meal_plan_code' => null,
                    'occupancy' => [],
                    'policies' => [],
                ]);
            }

            $account->update(['last_error' => null, 'last_synced_at' => now()]);

            return $map->fresh();
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchChannels(ChannelAccount $account, string $propertyId): array
    {
        $payload = $this->client->get($account, 'channels', ['propertyId' => $propertyId]);

        $entries = is_array($payload) ? ($payload['data'] ?? $payload) : null;

        if (! is_array($entries)) {
            throw new RuntimeException('Beds24 /channels returned an unexpected response (expected a channel list).');
        }

        return array_values(array_filter($entries, fn ($entry) => is_array($entry)));
    }

    /**
     * @param  array<int, array<string, mixed>>  $channels
     * @return array{propertyId: string, mappings: array<int, array<string, mixed>>}
     */
    private function bookingComConnection(array $channels, string $propertyId): array
    {
        $connection = null;

        foreach ($channels as $channel) {
            if (in_array(strtolower((string) ($channel['channel'] ?? '')), self::BOOKING_COM_CHANNEL_NAMES, true)) {
                $connection = $channel;
                break;
            }
        }

        if ($connection === null) {
            throw new RuntimeException(sprintf(
                'Beds24 returned no Booking.com channel connection for property %s. Add Booking.com in Beds24 (Settings > Channel Manager > Booking.com) and activate the connection.',
                $propertyId,
            ));
        }

        if (($connection['connected'] ?? true) === false) {
            throw new RuntimeException(sprintf(
                'The Booking.com connection for property %s is not active. Activate it in Beds24, then sync again.',
                $propertyId,
            ));
        }

        $mappings = array_values(array_filter($connection['mappings'] ?? [], fn ($mapping) => is_array($mapping)));

        if ($mappings === []) {
            throw new RuntimeException(sprintf(
                'The Booking.com connection for property %s has no room/rate mappings. Map your rooms and rate plans in Beds24 (Settings > Channel Manager > Booking.com > Get Codes), then sync again.',
                $propertyId,
            ));
        }

        return [
            'propertyId' => $this->mappingValue($connection, 'propertyId', $propertyId),
            'mappings' => $mappings,
        ];
    }

    /**
     * @param  array<string, mixed>  $mapping
     */
    private function mappingValue(array $mapping, string $key, string $default = ''): string
    {
        $snake = (string) preg_replace('/([a-z0-9])([A-Z])/', '$1_$2', $key);

        foreach ([$key, $snake] as $candidate) {
            if (array_key_exists($candidate, $mapping)) {
                return (string) $mapping[$candidate];
            }
        }

        return $default;
    }
}
