<?php

namespace Database\Factories;

use App\Models\Guest;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reservation>
 */
class ReservationFactory extends Factory
{
    protected $model = Reservation::class;

    public function definition(): array
    {
        $nights = fake()->numberBetween(1, 7);
        $base = fake()->randomFloat(2, 100, 500);
        $checkIn = fake()->dateTimeBetween('today', '+30 days');

        return [
            'reference' => 'CH-'.strtoupper(fake()->unique()->bothify('######')),
            'property_id' => Property::factory(),
            'room_id' => Room::factory(),
            'guest_id' => Guest::factory(),
            'check_in' => $checkIn->format('Y-m-d'),
            'check_out' => (clone $checkIn)->modify("+{$nights} days")->format('Y-m-d'),
            'guests_count' => 2,
            'status' => 'confirmed',
            'source' => 'direct',
            'channel' => null,
            'external_channel' => null,
            'external_booking_id' => null,
            'base_amount' => $base,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'fees_amount' => 0,
            'total_amount' => $base,
            'paid_amount' => $base,
            'payment_status' => 'paid',
            'sync_status' => 'none',
            'sync_attempts' => null,
            'notes' => null,
            'confirmed_at' => now(),
        ];
    }
}
