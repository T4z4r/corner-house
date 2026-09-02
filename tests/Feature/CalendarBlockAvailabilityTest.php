<?php

namespace Tests\Feature;

use App\Models\CalendarBlock;
use App\Models\Property;
use App\Models\Room;
use App\Services\Availability\AvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CalendarBlockAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    private AvailabilityService $availability;

    protected function setUp(): void
    {
        parent::setUp();
        $this->availability = app(AvailabilityService::class);
    }

    private function makeRoom(): Room
    {
        $property = Property::factory()->create();

        return Room::factory()->create([
            'property_id' => $property->id,
            'status' => 'active',
            'base_rate' => 100,
        ]);
    }

    private function range(): array
    {
        $checkIn = Carbon::now()->addDays(10)->startOfDay();

        return [$checkIn, $checkIn->copy()->addDays(2)];
    }

    public function test_availability_block_makes_room_unavailable(): void
    {
        $room = $this->makeRoom();
        [$checkIn, $checkOut] = $this->range();

        CalendarBlock::create([
            'property_id' => $room->property_id,
            'room_id' => $room->id,
            'type' => 'availability',
            'start_date' => $checkIn->toDateString(),
            'end_date' => $checkOut->toDateString(),
        ]);

        $this->assertFalse($this->availability->isRoomAvailable($room, $checkIn, $checkOut)['available']);
    }

    public function test_price_block_does_not_block_inventory(): void
    {
        $room = $this->makeRoom();
        [$checkIn, $checkOut] = $this->range();

        CalendarBlock::create([
            'property_id' => $room->property_id,
            'room_id' => $room->id,
            'type' => 'daily_price',
            'value' => 250,
            'start_date' => $checkIn->toDateString(),
            'end_date' => $checkOut->toDateString(),
        ]);

        $this->assertTrue($this->availability->isRoomAvailable($room, $checkIn, $checkOut)['available']);
    }

    public function test_stay_rule_block_does_not_block_inventory(): void
    {
        $room = $this->makeRoom();
        [$checkIn, $checkOut] = $this->range();

        CalendarBlock::create([
            'property_id' => $room->property_id,
            'room_id' => $room->id,
            'type' => 'min_stay',
            'min_stay' => 7,
            'start_date' => $checkIn->toDateString(),
            'end_date' => $checkOut->toDateString(),
        ]);

        $this->assertTrue($this->availability->isRoomAvailable($room, $checkIn, $checkOut)['available']);
    }

    public function test_inactive_availability_block_does_not_block_inventory(): void
    {
        $room = $this->makeRoom();
        [$checkIn, $checkOut] = $this->range();

        CalendarBlock::create([
            'property_id' => $room->property_id,
            'room_id' => $room->id,
            'type' => 'availability',
            'start_date' => $checkIn->toDateString(),
            'end_date' => $checkOut->toDateString(),
            'is_active' => false,
        ]);

        $this->assertTrue($this->availability->isRoomAvailable($room, $checkIn, $checkOut)['available']);
    }
}
