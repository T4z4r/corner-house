<?php

namespace Tests\Feature;

use App\Models\BookingHold;
use App\Models\CalendarBlock;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class WebsiteCalendarBlockedDaysTest extends TestCase
{
    use RefreshDatabase;

    private function makeProperty(): Property
    {
        return Property::factory()->create(['status' => 'active']);
    }

    private function makeRoom(Property $property): Room
    {
        return Room::factory()->create([
            'property_id' => $property->id,
            'status' => 'active',
            'base_rate' => 100,
        ]);
    }

    private function inThreeWeeks(): string
    {
        return Carbon::today()->addDays(21)->toDateString();
    }

    public function test_confirmed_reservation_blocks_nights_on_website_calendar(): void
    {
        $property = $this->makeProperty();
        $room = $this->makeRoom($property);
        $checkIn = $this->inThreeWeeks();

        Reservation::factory()->create([
            'property_id' => $property->id,
            'room_id' => $room->id,
            'check_in' => $checkIn,
            'check_out' => Carbon::parse($checkIn)->addDays(3)->toDateString(),
        ]);

        $this->getJson(route('booking.availability'))
            ->assertOk()
            ->assertJson([
                ['start' => $checkIn, 'end' => Carbon::parse($checkIn)->addDays(3)->toDateString()],
            ]);
    }

    public function test_cancelled_reservation_does_not_block_website_calendar(): void
    {
        $property = $this->makeProperty();
        $room = $this->makeRoom($property);
        $checkIn = $this->inThreeWeeks();

        Reservation::factory()->create([
            'property_id' => $property->id,
            'room_id' => $room->id,
            'check_in' => $checkIn,
            'check_out' => Carbon::parse($checkIn)->addDays(3)->toDateString(),
            'status' => 'cancelled',
        ]);

        $this->getJson(route('booking.availability'))
            ->assertOk()
            ->assertExactJson([]);
    }

    public function test_inventory_blocking_calendar_block_shows_as_blocked(): void
    {
        $property = $this->makeProperty();
        $room = $this->makeRoom($property);
        $start = Carbon::parse($this->inThreeWeeks());

        CalendarBlock::create([
            'property_id' => $property->id,
            'room_id' => $room->id,
            'type' => 'availability',
            'start_date' => $start->toDateString(),
            'end_date' => $start->copy()->addDays(2)->toDateString(),
            'is_active' => true,
        ]);

        $this->getJson(route('booking.availability'))
            ->assertOk()
            ->assertJson([
                // In-house blocks include the end night, so three nights are covered.
                ['start' => $start->toDateString(), 'end' => $start->copy()->addDays(3)->toDateString()],
            ]);
    }

    public function test_price_calendar_block_does_not_block_website_calendar(): void
    {
        $property = $this->makeProperty();
        $room = $this->makeRoom($property);
        $start = Carbon::parse($this->inThreeWeeks());

        CalendarBlock::create([
            'property_id' => $property->id,
            'room_id' => $room->id,
            'type' => 'daily_price',
            'value' => 250,
            'start_date' => $start->toDateString(),
            'end_date' => $start->copy()->addDays(2)->toDateString(),
            'is_active' => true,
        ]);

        $this->getJson(route('booking.availability'))
            ->assertOk()
            ->assertExactJson([]);
    }

    public function test_active_hold_blocks_website_calendar(): void
    {
        $property = $this->makeProperty();
        $room = $this->makeRoom($property);
        $checkIn = $this->inThreeWeeks();

        BookingHold::factory()->create([
            'property_id' => $property->id,
            'room_id' => $room->id,
            'check_in' => $checkIn,
            'check_out' => Carbon::parse($checkIn)->addDays(2)->toDateString(),
            'expires_at' => now()->addMinutes(15),
        ]);

        $this->getJson(route('booking.availability'))
            ->assertOk()
            ->assertJson([
                ['start' => $checkIn, 'end' => Carbon::parse($checkIn)->addDays(2)->toDateString()],
            ]);
    }

    public function test_property_wide_calendar_block_blocks_website_calendar(): void
    {
        $property = $this->makeProperty();
        $this->makeRoom($property);
        $start = Carbon::parse($this->inThreeWeeks());

        CalendarBlock::create([
            'property_id' => $property->id,
            'room_id' => null,
            'type' => 'manual',
            'start_date' => $start->toDateString(),
            'end_date' => $start->toDateString(),
            'is_active' => true,
        ]);

        $this->getJson(route('booking.availability'))
            ->assertOk()
            ->assertJson([
                ['start' => $start->toDateString(), 'end' => $start->copy()->addDay()->toDateString()],
            ]);
    }

    public function test_adjacent_sources_merge_into_single_range(): void
    {
        $property = $this->makeProperty();
        $room = $this->makeRoom($property);
        $checkIn = Carbon::parse($this->inThreeWeeks());

        Reservation::factory()->create([
            'property_id' => $property->id,
            'room_id' => $room->id,
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkIn->copy()->addDays(2)->toDateString(),
        ]);

        CalendarBlock::create([
            'property_id' => $property->id,
            'room_id' => $room->id,
            'type' => 'channel',
            'start_date' => $checkIn->copy()->addDays(2)->toDateString(),
            'end_date' => $checkIn->copy()->addDays(2)->toDateString(),
            'is_active' => true,
        ]);

        $this->getJson(route('booking.availability'))
            ->assertOk()
            ->assertJson([
                ['start' => $checkIn->toDateString(), 'end' => $checkIn->copy()->addDays(3)->toDateString()],
            ]);
    }
}
