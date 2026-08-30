<?php

namespace Database\Factories;

use App\Models\Amenity;
use Illuminate\Database\Eloquent\Factories\Factory;

class AmenityFactory extends Factory
{
    protected $model = Amenity::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'icon' => 'bi-check-circle',
            'description' => fake()->sentence(),
            'category' => fake()->randomElement(['Essentials', 'Kitchen', 'Entertainment', 'Outdoor', 'Convenience']),
        ];
    }
}
