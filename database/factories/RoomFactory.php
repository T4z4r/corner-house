<?php

namespace Database\Factories;

use App\Models\Property;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Room>
 */
class RoomFactory extends Factory
{
    protected $model = Room::class;

    public function definition(): array
    {
        return [
            'property_id' => Property::factory(),
            'name' => fake()->unique()->words(2, true),
            'slug' => null,
            'description' => fake()->paragraph(),
            'type' => fake()->randomElement(['en-suite', 'double', 'twin', 'studio', 'apartment']),
            'capacity' => 2,
            'sleeps' => 2,
            'bedrooms' => 1,
            'bathrooms' => 1,
            'is_private' => true,
            'status' => 'active',
            'base_rate' => fake()->randomFloat(2, 80, 250),
            'min_stay' => 1,
            'max_stay' => null,
        ];
    }
}
