<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\Reservation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'reservation_id' => Reservation::factory(),
            'provider' => 'stripe',
            'amount' => fake()->randomFloat(2, 80, 400),
            'currency' => 'GBP',
            'status' => 'pending',
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (): array => [
            'status' => 'paid',
            'provider_payment_id' => 'pi_'.fake()->unique()->bothify('##########'),
            'paid_at' => now(),
        ]);
    }
}
