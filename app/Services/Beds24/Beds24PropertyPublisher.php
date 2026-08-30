<?php

namespace App\Services\Beds24;

use App\Models\ChannelAccount;
use App\Models\ChannelMapping;
use App\Models\Property;
use Illuminate\Support\Facades\Log;

class Beds24PropertyPublisher
{
    public function __construct(private readonly Beds24Client $client) {}

    public function postProperty(Property $property): bool
    {
        $account = $this->activeAccount();
        if (! $account instanceof ChannelAccount) {
            return false;
        }

        $payload = $this->propertyPayload($account, $property);
        if ($payload === []) {
            return false;
        }

        try {
            $response = $this->client->post($account, 'properties', [$payload]);
            $this->syncMappings($account, $property, $response);

            return true;
        } catch (\Throwable $e) {
            Log::warning('Failed to post property to Beds24', [
                'property_id' => $property->id,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function activeAccount(): ?ChannelAccount
    {
        return ChannelAccount::query()
            ->where('provider', 'beds24')
            ->where('status', 'active')
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function propertyPayload(ChannelAccount $account, Property $property): array
    {
        $rooms = $property->rooms()->where('status', 'active')->orderBy('name')->get();
        $mapping = ChannelMapping::query()
            ->where('channel_account_id', $account->id)
            ->where('provider', 'beds24')
            ->where('property_id', $property->id)
            ->whereNull('room_id')
            ->whereNotNull('external_property_id')
            ->first();

        $roomTypes = [];
        foreach ($rooms as $room) {
            $roomMapping = ChannelMapping::query()
                ->where('channel_account_id', $account->id)
                ->where('provider', 'beds24')
                ->where('property_id', $property->id)
                ->where('room_id', $room->id)
                ->whereNotNull('external_room_id')
                ->first();

            $roomTypes[] = array_filter([
                'id' => $roomMapping?->external_room_id ? (int) $roomMapping->external_room_id : null,
                'name' => $room->name,
            ], static fn ($value) => $value !== null && $value !== '');
        }

        return array_filter([
            'id' => $mapping?->external_property_id ? (int) $mapping->external_property_id : null,
            'name' => $property->name,
            'currency' => $property->currency ?? config('app.currency', 'GBP'),
            'address' => $property->address_line_1,
            'city' => $property->city,
            'postcode' => $property->postcode,
            'country' => $property->country,
            'roomTypes' => $roomTypes,
        ], static fn ($value) => $value !== null && $value !== '' && $value !== []);
    }

    /**
     * @param  array<string, mixed>  $response
     */
    private function syncMappings(ChannelAccount $account, Property $property, array $response): void
    {
        $items = data_get($response, 'data', $response);
        if (! is_array($items)) {
            return;
        }

        $item = $items[0] ?? null;
        if (! is_array($item)) {
            return;
        }

        $externalPropertyId = (string) data_get($item, 'id', data_get($item, 'propertyId', ''));
        if ($externalPropertyId !== '') {
            ChannelMapping::query()->updateOrCreate(
                [
                    'channel_account_id' => $account->id,
                    'provider' => 'beds24',
                    'property_id' => $property->id,
                    'room_id' => null,
                ],
                [
                    'external_property_id' => $externalPropertyId,
                    'status' => 'active',
                ],
            );
        }

        $localRooms = $property->rooms()->where('status', 'active')->orderBy('name')->get()->values();
        $remoteRooms = data_get($item, 'roomTypes', []);
        if (! is_array($remoteRooms)) {
            return;
        }

        foreach ($localRooms as $index => $room) {
            $remoteRoom = $remoteRooms[$index] ?? null;
            if (! is_array($remoteRoom)) {
                continue;
            }

            $externalRoomId = (string) data_get($remoteRoom, 'id', '');
            if ($externalRoomId === '') {
                continue;
            }

            ChannelMapping::query()->updateOrCreate(
                [
                    'channel_account_id' => $account->id,
                    'provider' => 'beds24',
                    'property_id' => $property->id,
                    'room_id' => $room->id,
                ],
                [
                    'external_property_id' => $externalPropertyId !== '' ? $externalPropertyId : null,
                    'external_room_id' => $externalRoomId,
                    'status' => 'active',
                ],
            );
        }
    }
}
