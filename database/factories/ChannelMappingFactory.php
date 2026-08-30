<?php

namespace Database\Factories;

use App\Models\ChannelAccount;
use App\Models\ChannelMapping;
use App\Models\Property;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChannelMapping>
 */
class ChannelMappingFactory extends Factory
{
    protected $model = ChannelMapping::class;

    public function definition(): array
    {
        return [
            'channel_account_id' => ChannelAccount::factory(),
            'property_id' => Property::factory(),
            'room_id' => Room::factory(),
            'provider' => 'beds24',
            'external_property_id' => (string) fake()->numberBetween(1000, 9999),
            'external_room_id' => (string) fake()->numberBetween(1000, 9999),
            'status' => 'active',
        ];
    }
}
