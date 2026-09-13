<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\Room;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class WebsitePricingDisplayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::updateOrCreate(['key' => 'min_price_weekday'], ['group' => 'booking', 'value' => '550', 'label' => 'Min weekday', 'cast' => 'decimal:2']);
        Setting::updateOrCreate(['key' => 'min_price_weekend'], ['group' => 'booking', 'value' => '625', 'label' => 'Min weekend', 'cast' => 'decimal:2']);
        Setting::updateOrCreate(['key' => 'direct_booking_discount'], ['group' => 'booking', 'value' => '10', 'label' => 'Direct booking discount', 'cast' => 'integer']);
        Setting::updateOrCreate(['key' => 'cleaning_fee'], ['group' => 'booking', 'value' => '0', 'label' => 'Cleaning', 'cast' => 'decimal:2']);
        Setting::updateOrCreate(['key' => 'damage_deposit'], ['group' => 'booking', 'value' => '0', 'label' => 'Deposit', 'cast' => 'decimal:2']);

        cache()->forget('settings.all');
    }

    private function makeActiveRoom(Property $property): Room
    {
        return Room::factory()->create([
            'property_id' => $property->id,
            'name' => 'Garden Room',
            'status' => 'active',
            'base_rate' => 550,
            'capacity' => 2,
        ]);
    }

    public function test_property_page_shows_weekday_and_weekend_rates(): void
    {
        $property = Property::factory()->create(['status' => 'active']);
        $this->makeActiveRoom($property);

        $this->get(route('property'))
            ->assertOk()
            ->assertSee('Weekday £550', false)
            ->assertSee('Weekend £625', false)
            ->assertSee('direct-booking discount', false);
    }

    public function test_room_detail_page_shows_weekday_and_weekend_rates(): void
    {
        $property = Property::factory()->create(['status' => 'active']);
        $room = $this->makeActiveRoom($property);

        $this->get(route('property.room', $room))
            ->assertOk()
            ->assertSee('Weekday £550', false)
            ->assertSee('Weekend £625', false)
            ->assertSee('Weekday / night')
            ->assertSee('Weekend / night')
            ->assertSee('direct-booking discount', false);
    }

    public function test_booking_widget_config_carries_weekday_weekend_and_discount_values(): void
    {
        $property = Property::factory()->create(['status' => 'active']);
        $this->makeActiveRoom($property);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('window.__SITE__', false)
            ->assertSee('"weekdayRate":550', false)
            ->assertSee('"weekendRate":625', false)
            ->assertSee('"directDiscount":10', false)
            ->assertSee('Direct-booking discount (10%)', false);
    }

    public function test_booking_details_breaks_down_each_night_rate(): void
    {
        $property = Property::factory()->create(['status' => 'active']);
        $room = $this->makeActiveRoom($property);

        $checkIn = Carbon::parse('next monday')->startOfDay();
        $checkOut = $checkIn->copy()->addDays(3);

        $response = $this->get(route('booking.details', [
            'room' => $room,
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
            'guests' => 1,
        ]))->assertOk();

        for ($i = 0; $i < 3; $i++) {
            $response->assertSee($checkIn->copy()->addDays($i)->format('D j M Y'), false);
        }

        $response->assertSee('Nightly rate', false)->assertSee('£550.00', false);
    }

    public function test_booking_details_shows_direct_discount_line(): void
    {
        $property = Property::factory()->create(['status' => 'active']);
        $room = $this->makeActiveRoom($property);

        $checkIn = now()->addDays(10)->startOfDay();
        $checkOut = $checkIn->copy()->addDays(2);

        $this->get(route('booking.details', [
            'room' => $room,
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
            'guests' => 1,
        ]))
            ->assertOk()
            ->assertSee('Direct-booking discount (10%)', false)
            ->assertSee('Direct rate &mdash; this price includes our', false);
    }
}
