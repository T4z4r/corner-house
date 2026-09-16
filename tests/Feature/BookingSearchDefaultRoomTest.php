<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\Room;
use App\Models\Setting;
use App\Services\Booking\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BookingSearchDefaultRoomTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::updateOrCreate(['key' => 'min_price_weekday'], ['value' => '0', 'group' => 'booking', 'label' => 'Min weekday', 'cast' => 'decimal:2']);
        Setting::updateOrCreate(['key' => 'min_price_weekend'], ['value' => '0', 'group' => 'booking', 'label' => 'Min weekend', 'cast' => 'decimal:2']);
        Setting::updateOrCreate(['key' => 'cleaning_fee'], ['value' => '0', 'group' => 'booking', 'label' => 'Cleaning', 'cast' => 'decimal:2']);
        Setting::updateOrCreate(['key' => 'damage_deposit'], ['value' => '0', 'group' => 'booking', 'label' => 'Deposit', 'cast' => 'decimal:2']);
        Setting::updateOrCreate(['key' => 'direct_booking_discount'], ['value' => '0', 'group' => 'booking', 'label' => 'Discount', 'cast' => 'decimal:2']);
    }

    /**
     * @return array{0: Property, 1: Room, 2: Property, 3: Room}
     */
    private function listings(bool $marinaRoomPrimary = false): array
    {
        $cornerHouse = Property::factory()->create(['name' => 'Corner House', 'status' => 'active', 'is_primary' => true, 'capacity' => 12]);
        $lion = Room::factory()->create([
            'property_id' => $cornerHouse->id,
            'name' => 'Lion',
            'status' => 'active',
            'base_rate' => 100,
            'capacity' => 2,
        ]);

        $marina = Property::factory()->create([
            'name' => 'Corner House - Large country house next to marina',
            'status' => 'active',
            'is_primary' => false,
            'capacity' => 1,
        ]);
        $marinaRoom = Room::factory()->create([
            'property_id' => $marina->id,
            'name' => 'Corner House - Large country house next to marina',
            'status' => 'active',
            'base_rate' => 100,
            'capacity' => 12,
            'is_primary' => $marinaRoomPrimary,
        ]);

        return [$cornerHouse, $lion, $marina, $marinaRoom];
    }

    private function dates(): array
    {
        $checkIn = Carbon::today()->addDays(21)->startOfDay();

        return [$checkIn->toDateString(), $checkIn->copy()->addDays(2)->toDateString()];
    }

    public function test_search_defaults_to_the_primary_listing_when_its_room_is_available(): void
    {
        [$cornerHouse, $lion, $marina, $marinaRoom] = $this->listings(true);
        [$checkIn, $checkOut] = $this->dates();

        $this->get(route('booking.search', [
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'guests' => 2,
        ]))
            ->assertOk()
            ->assertSee($marina->name)
            ->assertSee(route('booking.details', [
                'room' => $marinaRoom,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'guests' => 2,
            ]))
            ->assertDontSee(route('booking.details', [
                'room' => $lion,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'guests' => 2,
            ]));
    }

    public function test_search_falls_back_when_the_primary_room_is_unavailable(): void
    {
        [$cornerHouse, $lion, $marina, $marinaRoom] = $this->listings(true);
        [$checkIn, $checkOut] = $this->dates();

        app(BookingService::class)->create([
            'room_id' => $marinaRoom->id,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'guests_count' => 2,
            'status' => 'confirmed',
        ]);

        $this->get(route('booking.search', [
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'guests' => 2,
        ]))
            ->assertOk()
            ->assertSee($cornerHouse->name)
            ->assertSee(route('booking.details', [
                'room' => $lion,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'guests' => 2,
            ]))
            ->assertDontSee(route('booking.details', [
                'room' => $marinaRoom,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'guests' => 2,
            ]));
    }

    public function test_search_defaults_to_the_first_active_room_when_no_room_is_primary(): void
    {
        [$cornerHouse, $lion, $marina, $marinaRoom] = $this->listings();
        [$checkIn, $checkOut] = $this->dates();

        $this->get(route('booking.search', [
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'guests' => 2,
        ]))
            ->assertOk()
            ->assertSee($cornerHouse->name)
            ->assertSee(route('booking.details', [
                'room' => $lion,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'guests' => 2,
            ]))
            ->assertDontSee(route('booking.details', [
                'room' => $marinaRoom,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'guests' => 2,
            ]));
    }

    public function test_search_can_offer_the_whole_house_on_the_primary_listing(): void
    {
        [$cornerHouse, $lion, $marina, $marinaRoom] = $this->listings(true);
        [$checkIn, $checkOut] = $this->dates();

        $this->get(route('booking.search', [
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'guests' => 8,
        ]))
            ->assertOk()
            ->assertSee('Sleeps 12')
            ->assertSee(route('booking.details', [
                'room' => $marinaRoom,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'guests' => 8,
            ]));
    }

    public function test_search_still_respects_an_explicit_property_choice(): void
    {
        [$cornerHouse, $lion, $marina, $marinaRoom] = $this->listings();
        [$checkIn, $checkOut] = $this->dates();

        $this->get(route('booking.search', [
            'property_id' => $cornerHouse->getRouteKey(),
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'guests' => 2,
        ]))
            ->assertOk()
            ->assertSee(route('booking.details', [
                'room' => $lion,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'guests' => 2,
            ]))
            ->assertDontSee(route('booking.details', [
                'room' => $marinaRoom,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'guests' => 2,
            ]));
    }
}
