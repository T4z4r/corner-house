<?php

namespace Database\Factories;

use App\Models\Review;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'stars' => fake()->numberBetween(4, 5),
            'quote' => fake()->paragraph(),
            'cite' => fake()->name().', '.fake()->monthName().' '.fake()->year(),
            'status' => Review::STATUS_HIDDEN,
            'sort_order' => 0,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (): array => ['status' => Review::STATUS_APPROVED]);
    }

    public function hidden(): static
    {
        return $this->state(fn (): array => ['status' => Review::STATUS_HIDDEN]);
    }
}
