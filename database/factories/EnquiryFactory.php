<?php

namespace Database\Factories;

use App\Models\Enquiry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Enquiry>
 */
class EnquiryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $checkIn = fake()->dateTimeBetween('+1 week', '+3 months');

        return [
            'type' => Enquiry::TYPE_BOOKING,
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'guests' => (string) fake()->numberBetween(2, 12),
            'check_in' => $checkIn->format('Y-m-d'),
            'check_out' => $checkIn->modify('+'.fake()->numberBetween(2, 7).' days')->format('Y-m-d'),
            'nights' => fake()->numberBetween(2, 7),
            'message' => fake()->sentence(),
            'drinks_package' => fake()->boolean(),
            'terms_accepted' => true,
            'status' => Enquiry::STATUS_NEW,
        ];
    }

    public function booking(): static
    {
        return $this->state(fn (): array => ['type' => Enquiry::TYPE_BOOKING]);
    }

    public function contact(): static
    {
        return $this->state(fn (): array => ['type' => Enquiry::TYPE_CONTACT]);
    }

    public function read(): static
    {
        return $this->state(fn (): array => ['status' => Enquiry::STATUS_READ]);
    }
}
