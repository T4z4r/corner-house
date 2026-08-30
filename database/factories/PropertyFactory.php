<?php

namespace Database\Factories;

use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Property>
 */
class PropertyFactory extends Factory
{
    protected $model = Property::class;

    public function definition(): array
    {
        $name = fake()->unique()->streetName().' House';

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->paragraph(),
            'short_description' => fake()->sentence(),
            'address_line_1' => fake()->streetAddress(),
            'city' => fake()->city(),
            'postcode' => fake()->postcode(),
            'country' => 'GB',
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'capacity' => 4,
            'bedrooms' => 2,
            'bathrooms' => 1,
            'status' => 'active',
            'currency' => 'GBP',
        ];
    }
}
