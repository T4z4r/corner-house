<?php

namespace App\Services\Beds24;

use App\Models\ChannelAccount;
use App\Models\ChannelRateMap;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use SimpleXMLElement;

class Beds24MappingService
{
    /**
     * Fetch the Booking.com room/rate mapping XML for a Beds24 property.
     */
    public function fetch(string $propertyId): string
    {
        $url = (string) config('services.beds24.booking_mapping_url', 'https://beds24.com/api/booking.com/getmapping.php');

        $response = Http::connectTimeout(5)
            ->timeout(25)
            ->accept('application/xml')
            ->get($url, ['propid' => $propertyId]);

        if ($response->failed()) {
            throw new RuntimeException('Beds24 booking mapping request failed ('.$response->status().').');
        }

        return $response->body();
    }

    /**
     * Parse the getmapping XML into a structured array.
     *
     * @return array{rooms: array<int, array<string, mixed>>}
     */
    public function parse(string $xml): array
    {
        $rooms = [];

        $root = $this->asXml($xml);

        foreach ($root->rooms->room as $room) {
            if (! $room instanceof SimpleXMLElement) {
                continue;
            }

            $rates = [];

            foreach ($room->rates->rate as $rate) {
                if (! $rate instanceof SimpleXMLElement) {
                    continue;
                }

                $rates[] = [
                    'external_rate_id' => (string) ($rate['id'] ?? ''),
                    'rate_name' => (string) ($rate['rate_name'] ?? ''),
                    'policy' => $this->attribute($rate, 'policy'),
                    'policy_id' => $this->attribute($rate, 'policy_id'),
                    'max_persons' => $this->attribute($rate, 'max_persons') !== null ? (int) $this->attribute($rate, 'max_persons') : null,
                    'fixed_occupancy' => $this->attribute($rate, 'fixed_occupancy') !== null ? (int) $this->attribute($rate, 'fixed_occupancy') : null,
                    'is_child_rate' => $this->attribute($rate, 'is_child_rate') === '1',
                    'meal_plan_code' => isset($rate->meal_plan['meal_plan_code']) ? (string) $rate->meal_plan['meal_plan_code'] : null,
                    'pricing_type' => isset($rate->pricing['type']) ? (string) $rate->pricing['type'] : null,
                    'occupancy' => $this->parseOccupancy($rate),
                    'rate_relation' => $this->parseRateRelation($rate),
                    'policies' => $this->parsePolicies($rate),
                ];
            }

            $rooms[] = [
                'external_room_id' => (string) ($room['id'] ?? ''),
                'hotel_id' => (string) ($room['hotel_id'] ?? ''),
                'hotel_name' => (string) ($room['hotel_name'] ?? ''),
                'room_name' => (string) ($room['room_name'] ?? ''),
                'rates' => $rates,
            ];
        }

        return ['rooms' => $rooms];
    }

    /**
     * Fetch and store the Booking.com mapping for a property, replacing any previous copy.
     */
    public function sync(ChannelAccount $account, string $propertyId): ChannelRateMap
    {
        $xml = $this->fetch($propertyId);
        $parsed = $this->parse($xml);

        return DB::transaction(function () use ($account, $propertyId, $xml, $parsed) {
            $map = ChannelRateMap::query()->updateOrCreate(
                [
                    'channel_account_id' => $account->id,
                    'external_property_id' => $propertyId,
                ],
                [
                    'hotel_id' => $parsed['rooms'][0]['hotel_id'] ?? null,
                    'hotel_name' => $parsed['rooms'][0]['hotel_name'] ?? null,
                    'raw_xml' => $xml,
                    'synced_at' => now(),
                ],
            );

            $map->rates()->delete();

            foreach ($parsed['rooms'] as $room) {
                foreach ($room['rates'] as $rate) {
                    $relation = $rate['rate_relation'];

                    $map->rates()->create([
                        'external_room_id' => $room['external_room_id'],
                        'room_name' => $room['room_name'],
                        'external_rate_id' => $rate['external_rate_id'],
                        'rate_name' => $rate['rate_name'],
                        'policy' => $rate['policy'],
                        'policy_id' => $rate['policy_id'],
                        'max_persons' => $rate['max_persons'],
                        'fixed_occupancy' => $rate['fixed_occupancy'],
                        'is_child_rate' => $rate['is_child_rate'],
                        'parent_rate_id' => $relation['parent_rate_id'] ?? null,
                        'follows_price' => $relation['follows_price'] ?? null,
                        'percentage' => $relation['percentage'] ?? null,
                        'pricing_type' => $rate['pricing_type'],
                        'meal_plan_code' => $rate['meal_plan_code'],
                        'occupancy' => $rate['occupancy'],
                        'policies' => $rate['policies'],
                    ]);
                }
            }

            $account->update(['last_error' => null, 'last_synced_at' => now()]);

            return $map->fresh();
        });
    }

    private function asXml(string $xml): SimpleXMLElement
    {
        $root = @simplexml_load_string(trim($xml));

        if ($root === false) {
            throw new RuntimeException('Beds24 booking mapping returned invalid XML.');
        }

        return $root;
    }

    private function attribute(SimpleXMLElement $node, string $name): ?string
    {
        return isset($node[$name]) ? (string) $node[$name] : null;
    }

    /**
     * @return array<int, array{persons: int, percentage: float, round: int}>
     */
    private function parseOccupancy(SimpleXMLElement $rate): array
    {
        $occupancy = [];

        foreach ($rate->pricing->occupancy as $entry) {
            $occupancy[] = [
                'persons' => $this->attribute($entry, 'persons') !== null ? (int) $this->attribute($entry, 'persons') : 0,
                'percentage' => $this->attribute($entry, 'percentage') !== null ? (float) $this->attribute($entry, 'percentage') : 0.0,
                'round' => $this->attribute($entry, 'round') !== null ? (int) $this->attribute($entry, 'round') : 0,
            ];
        }

        return $occupancy;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function parseRateRelation(SimpleXMLElement $rate): ?array
    {
        if (! isset($rate->rate_relation)) {
            return null;
        }

        $relation = $rate->rate_relation;

        return [
            'follows_closed' => $this->attribute($relation, 'follows_closed'),
            'follows_restrictions' => $this->attribute($relation, 'follows_restrictions'),
            'follows_policygroup_id' => $this->attribute($relation, 'follows_policygroup_id'),
            'follows_price' => $this->attribute($relation, 'follows_price'),
            'parent_rate_id' => $this->attribute($relation, 'parent_rate_id'),
            'percentage' => $this->attribute($relation, 'percentage') !== null ? (float) $this->attribute($relation, 'percentage') : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function parsePolicies(SimpleXMLElement $rate): array
    {
        $policies = [];

        $guarantee = $rate->policies->guarantee_payment_policy->guarantee_payment ?? null;

        if ($guarantee instanceof SimpleXMLElement) {
            $policies['guarantee_payment'] = [
                'policy_code' => $this->attribute($guarantee, 'policy_code'),
                'effective_from' => $this->attribute($guarantee, 'effective_from'),
                'required' => $this->attribute($guarantee, 'required') !== null ? (int) $this->attribute($guarantee, 'required') : null,
            ];
        }

        $cancel = $rate->policies->cancel_policy->cancel_penalty ?? null;

        if ($cancel instanceof SimpleXMLElement) {
            $policies['cancel_penalty'] = [
                'policy_code' => $this->attribute($cancel, 'policy_code'),
            ];
        }

        return $policies;
    }
}
