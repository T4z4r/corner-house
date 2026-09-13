<?php

namespace App\Services\Beds24;

use App\Models\ChannelAccount;
use App\Models\ChannelRateMap;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use SimpleXMLElement;

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
     * Enrich a property's Booking.com mapping from pasted getmapping XML.
     *
     * The legacy getmapping.php feed only answers to a browser signed into
     * Beds24, so the admin copies the XML and pastes it here. This replaces the
     * stored snapshot for the property with the richer details the API v2 feed
     * does not expose (rate names, max occupancy, policies and meal plans).
     */
    public function importXml(ChannelAccount $account, string $propertyId, string $xml): ChannelRateMap
    {
        $document = $this->parse($xml);

        return DB::transaction(function () use ($account, $propertyId, $document, $xml) {
            $map = ChannelRateMap::query()->updateOrCreate(
                [
                    'channel_account_id' => $account->id,
                    'external_property_id' => $propertyId,
                ],
                [
                    'hotel_id' => $document['hotel_id'],
                    'hotel_name' => $document['hotel_name'],
                    'raw_xml' => $xml,
                    'synced_at' => now(),
                ],
            );

            $map->rates()->delete();

            foreach ($document['rooms'] as $room) {
                foreach ($room['rates'] as $rate) {
                    $map->rates()->create([
                        'external_room_id' => $room['id'],
                        'room_name' => $room['room_name'],
                        'external_rate_id' => $rate['id'],
                        'rate_name' => $rate['rate_name'],
                        'policy' => $rate['policy'],
                        'policy_id' => $rate['policy_id'],
                        'max_persons' => $rate['max_persons'],
                        'fixed_occupancy' => $rate['fixed_occupancy'],
                        'is_child_rate' => $rate['is_child_rate'],
                        'parent_rate_id' => $rate['parent_rate_id'],
                        'follows_price' => $rate['follows_price'],
                        'percentage' => $rate['percentage'],
                        'pricing_type' => $rate['pricing_type'],
                        'meal_plan_code' => $rate['meal_plan_code'],
                        'occupancy' => $rate['occupancy'],
                        'policies' => $rate['policies'],
                    ]);
                }
            }

            $account->update(['last_error' => null]);

            return $map->fresh();
        });
    }

    /**
     * @return array{
     *     hotel_id: ?string,
     *     hotel_name: ?string,
     *     rooms: array<int, array{
     *         id: string,
     *         room_name: ?string,
     *         rates: array<int, array<string, mixed>>
     *     }>
     * }
     */
    private function parse(string $xml): array
    {
        // A browser-inspector text banner ("This XML file does not appear...")
        // can precede the real document when the XML is copied from the view.
        $firstTag = strpos($xml, '<');

        if ($firstTag !== false && $firstTag > 0) {
            $xml = substr($xml, $firstTag);
        }

        $previous = libxml_use_internal_errors(true);
        $document = simplexml_load_string($xml);
        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if ($document === false) {
            $detail = $errors !== [] ? trim($errors[0]->message) : 'invalid XML';

            throw new RuntimeException('The pasted Beds24 mapping is not valid XML ('.$detail.').');
        }

        $roomsNode = $document->rooms ?? null;

        if ($roomsNode === null || $roomsNode->room === null) {
            throw new RuntimeException('The pasted content does not look like a Beds24 <roomrates> mapping (no <rooms><room> elements).');
        }

        $hotelId = null;
        $hotelName = null;
        $rooms = [];

        foreach ($roomsNode->room as $room) {
            $hotelId ??= $this->stringOrNull($room['hotel_id']);
            $hotelName ??= $this->stringOrNull($room['hotel_name']);

            $rates = [];

            foreach (($room->rates->rate ?? []) as $rate) {
                $rates[] = $this->extractRate($rate);
            }

            $rooms[] = [
                'id' => (string) $room['id'],
                'room_name' => $this->stringOrNull($room['room_name']),
                'rates' => $rates,
            ];
        }

        return [
            'hotel_id' => $hotelId,
            'hotel_name' => $hotelName,
            'rooms' => $rooms,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function extractRate(SimpleXMLElement $rate): array
    {
        $parentId = $this->stringOrNull($rate['parent_rate_id']);
        $pricing = $rate->pricing ?? null;

        return [
            'id' => (string) $rate['id'],
            'rate_name' => $this->stringOrNull($rate['rate_name']) ?? '',
            'policy' => $this->stringOrNull($rate['policy']),
            'policy_id' => $this->stringOrNull($rate['policy_id']),
            'max_persons' => $this->intOrNull($rate['max_persons']),
            'fixed_occupancy' => $this->intOrNull($rate['fixed_occupancy']),
            'is_child_rate' => $this->nullableBool($rate['is_child_rate'] ?? null) ?? ($parentId !== null),
            'parent_rate_id' => $parentId,
            'follows_price' => $this->nullableBool($rate['follows_price'] ?? null),
            'percentage' => $this->floatOrNull($rate['percentage']),
            'pricing_type' => $this->stringOrNull($pricing['type'] ?? null),
            'meal_plan_code' => $this->stringOrNull($rate->meal_plan['meal_plan_code'] ?? null),
            'occupancy' => $this->extractOccupancy($pricing),
            'policies' => $this->extractPolicies($rate->policies ?? null),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function extractOccupancy(?SimpleXMLElement $pricing): array
    {
        if ($pricing === null) {
            return [];
        }

        $occupancies = [];

        foreach ($pricing->occupancy as $occupancy) {
            $occupancies[] = [
                'persons' => (int) $occupancy['persons'],
                'percentage' => $this->floatOrNull($occupancy['percentage']),
                'round' => $this->intOrNull($occupancy['round']) ?? 0,
            ];
        }

        return $occupancies;
    }

    /**
     * @return array<string, mixed>
     */
    private function extractPolicies(?SimpleXMLElement $policies): array
    {
        if ($policies === null) {
            return [];
        }

        $result = [];

        foreach ($policies->guarantee_payment_policy->guarantee_payment ?? [] as $guarantee) {
            $result['guarantee_payment'][] = [
                'policy_code' => (string) $guarantee['policy_code'],
                'effective_from' => $this->stringOrNull($guarantee['effective_from']),
                'required' => $this->nullableBool($guarantee['required'] ?? null) ?? false,
            ];
        }

        foreach ($policies->cancel_policy->cancel_penalty ?? [] as $penalty) {
            $result['cancel_penalty'][] = [
                'policy_code' => (string) $penalty['policy_code'],
                'amount' => $this->stringOrNull($penalty['amount']),
                'days_before' => $this->stringOrNull($penalty['days_before']),
            ];
        }

        foreach ($policies->booking_rules->booking_rule ?? [] as $rule) {
            $attributes = [];

            foreach ($rule->attributes() as $key => $value) {
                $attributes[(string) $key] = (string) $value;
            }

            $result['booking_rules'][] = $attributes;
        }

        return $result;
    }

    private function stringOrNull(mixed $value): ?string
    {
        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }

    private function intOrNull(mixed $value): ?int
    {
        $string = trim((string) $value);

        if ($string === '' || ! is_numeric($string)) {
            return null;
        }

        return (int) $string;
    }

    private function floatOrNull(mixed $value): ?float
    {
        $string = trim((string) $value);

        if ($string === '' || ! is_numeric($string)) {
            return null;
        }

        return (float) $string;
    }

    private function nullableBool(mixed $value): ?bool
    {
        $string = trim((string) $value);

        if ($string === '' || ! in_array($string, ['0', '1', 'true', 'false'], true)) {
            return null;
        }

        return in_array($string, ['1', 'true'], true);
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
