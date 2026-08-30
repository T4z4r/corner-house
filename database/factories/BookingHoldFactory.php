<?php

namespace Database\Factories;

use App\Models\BookingHold;
use App\Models\Property;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<BookingHold>
 */
class BookingHoldFactory extends Factory
{
    protected $model = BookingHold::class;

    public function definition(): array
    {
        $checkIn = now()->addDays(5)->startOfDay();

        return [
            'property_id' => Property::factory(),
            'room_id' => Room::factory(),
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkIn->copy()->addDays(2)->toDateString(),
            'session_id' => Str::uuid()->toString(),
            'status' => 'active',
            'quoted_total' => 200,
            'expires_at' => now()->addMinutes(15),
        ];
    }
}
