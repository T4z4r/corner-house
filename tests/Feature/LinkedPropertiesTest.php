<?php

namespace Tests\Feature;

use App\Models\CalendarBlock;
use App\Models\Guest;
use App\Models\PricingOverride;
use App\Models\PricingRule;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\Setting;
use App\Models\User;
use App\Services\Availability\AvailabilityService;
use App\Services\Pricing\PricingEngine;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LinkedPropertiesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        Setting::firstOrCreate(['key' => 'min_price_weekday'], ['value' => '0', 'group' => 'booking', 'label' => 'Min weekday', 'cast' => 'decimal:2']);
        Setting::firstOrCreate(['key' => 'min_price_weekend'], ['value' => '0', 'group' => 'booking', 'label' => 'Min weekend', 'cast' => 'decimal:2']);
        Setting::firstOrCreate(['key' => 'cleaning_fee'], ['value' => '0', 'group' => 'booking', 'label' => 'Cleaning', 'cast' => 'decimal:2']);
    }

    /**
     * Two active properties linked both ways, as the admin screen maintains.
     *
     * @return array{0: Property, 1: Property}
     */
    private function linkedProperties(): array
    {
        $main = Property::factory()->create(['status' => 'active', 'is_primary' => true]);
        $partner = Property::factory()->create(['status' => 'active', 'is_primary' => false]);

        $main->update(['linked_property_id' => $partner->id]);
        $partner->update(['linked_property_id' => $main->id]);

        return [$main, $partner];
    }

    private function nights(): array
    {
        $checkIn = Carbon::today()->addDays(21)->startOfDay();

        return [$checkIn, $checkIn->copy()->addDays(3)];
    }

    public function test_reservation_on_the_linked_property_blocks_the_main_property_room(): void
    {
        [$main, $partner] = $this->linkedProperties();
        $mainRoom = Room::factory()->create(['property_id' => $main->id, 'status' => 'active']);
        $partnerRoom = Room::factory()->create(['property_id' => $partner->id, 'status' => 'active']);
        [$checkIn, $checkOut] = $this->nights();

        Reservation::factory()->create([
            'property_id' => $partner->id,
            'room_id' => $partnerRoom->id,
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
            'status' => 'confirmed',
        ]);

        $result = app(AvailabilityService::class)->isRoomAvailable($mainRoom, $checkIn, $checkOut);

        $this->assertFalse($result['available']);
        $this->assertContains('Overlapping confirmed or pending reservation', $result['conflicts']);
    }

    public function test_inventory_block_on_the_linked_property_blocks_the_main_room(): void
    {
        [$main, $partner] = $this->linkedProperties();
        $mainRoom = Room::factory()->create(['property_id' => $main->id, 'status' => 'active']);
        [$checkIn, $checkOut] = $this->nights();

        CalendarBlock::create([
            'property_id' => $partner->id,
            'room_id' => null,
            'type' => 'availability',
            'start_date' => $checkIn->toDateString(),
            'end_date' => $checkOut->copy()->subDay()->toDateString(),
            'is_active' => true,
        ]);

        $result = app(AvailabilityService::class)->isRoomAvailable($mainRoom, $checkIn, $checkOut);

        $this->assertFalse($result['available']);
        $this->assertContains('Room is blocked for this period', $result['conflicts']);
    }

    public function test_website_availability_merges_blocked_nights_from_both_listings(): void
    {
        [$main, $partner] = $this->linkedProperties();
        Room::factory()->create(['property_id' => $main->id, 'status' => 'active']);
        $partnerRoom = Room::factory()->create(['property_id' => $partner->id, 'status' => 'active']);
        [$checkIn, $checkOut] = $this->nights();

        Reservation::factory()->create([
            'property_id' => $partner->id,
            'room_id' => $partnerRoom->id,
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
            'status' => 'confirmed',
        ]);

        // The public widget resolves the main property and must see the
        // partner's bookings on the same calendar.
        $this->getJson(route('booking.availability'))
            ->assertOk()
            ->assertJson([['start' => $checkIn->toDateString(), 'end' => $checkOut->toDateString()]]);

        // The same shared calendar is returned when asked from the partner side.
        $this->assertSame(
            [['start' => $checkIn->toDateString(), 'end' => $checkOut->toDateString()]],
            app(AvailabilityService::class)->websiteBlockedRanges($partner->id),
        );
    }

    public function test_search_page_offers_both_listings_and_lists_the_selected_one(): void
    {
        [$main, $partner] = $this->linkedProperties();
        Room::factory()->create(['property_id' => $main->id, 'name' => 'Main Suite', 'status' => 'active']);
        Room::factory()->create(['property_id' => $partner->id, 'name' => 'Partner Suite', 'status' => 'active']);
        [$checkIn, $checkOut] = $this->nights();

        $this->get(route('booking.search'))
            ->assertOk()
            ->assertSee($main->name)
            ->assertSee($partner->name);

        $this->get(route('booking.search', [
            'property_id' => $partner->id,
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
            'guests' => 1,
        ]))
            ->assertOk()
            ->assertSee('Partner Suite')
            ->assertDontSee('Main Suite');
    }

    public function test_price_rule_scoped_to_the_linked_property_applies_to_the_main_room(): void
    {
        [$main, $partner] = $this->linkedProperties();
        $mainRoom = Room::factory()->create(['property_id' => $main->id, 'status' => 'active', 'base_rate' => 100]);
        $date = now()->addDays(20);

        PricingRule::create([
            'property_id' => $partner->id,
            'name' => 'Linked seasonal',
            'rule_type' => 'seasonal',
            'start_date' => $date->copy()->startOfMonth(),
            'end_date' => $date->copy()->endOfMonth(),
            'adjustment_type' => 'percent',
            'adjustment_value' => -20,
            'priority' => 10,
            'is_enabled' => true,
        ]);

        $this->assertSame(80.0, app(PricingEngine::class)->calculateRateForDate($mainRoom, $date));
    }

    public function test_manual_override_on_the_linked_room_applies_to_the_main_room(): void
    {
        [$main, $partner] = $this->linkedProperties();
        $mainRoom = Room::factory()->create(['property_id' => $main->id, 'status' => 'active', 'base_rate' => 100]);
        $partnerRoom = Room::factory()->create(['property_id' => $partner->id, 'status' => 'active']);
        $date = now()->addDays(5);

        PricingOverride::create([
            'room_id' => $partnerRoom->id,
            'start_date' => $date->copy()->subDay(),
            'end_date' => $date->copy()->addDay(),
            'rate' => 75,
            'is_enabled' => true,
        ]);

        $this->assertSame(75.0, app(PricingEngine::class)->calculateRateForDate($mainRoom, $date));
    }

    public function test_occupancy_rule_counts_sales_across_the_linked_listings(): void
    {
        [$main, $partner] = $this->linkedProperties();
        $mainRoom = Room::factory()->create(['property_id' => $main->id, 'status' => 'active', 'base_rate' => 100]);
        $partnerRoom = Room::factory()->create(['property_id' => $partner->id, 'status' => 'active']);
        $date = now()->addDays(30)->startOfDay();

        Reservation::factory()->create([
            'property_id' => $partner->id,
            'room_id' => $partnerRoom->id,
            'check_in' => $date->toDateString(),
            'check_out' => $date->copy()->addDay()->toDateString(),
            'status' => 'confirmed',
        ]);

        PricingRule::create([
            'property_id' => null,
            'room_id' => null,
            'name' => 'Half full uplift',
            'rule_type' => 'occupancy',
            'occupancy_threshold' => 40,
            'adjustment_type' => 'percent',
            'adjustment_value' => 50,
            'priority' => 5,
            'is_enabled' => true,
        ]);

        // One of the two linked rooms is sold (50%), above the 40% threshold.
        $this->assertSame(150.0, app(PricingEngine::class)->calculateRateForDate($mainRoom, $date));
    }

    public function test_pricing_rules_do_not_leak_between_unlinked_properties(): void
    {
        $main = Property::factory()->create(['status' => 'active']);
        $other = Property::factory()->create(['status' => 'active']);
        $mainRoom = Room::factory()->create(['property_id' => $main->id, 'status' => 'active', 'base_rate' => 100]);
        $date = now()->addDays(20);

        PricingRule::create([
            'property_id' => $other->id,
            'name' => 'Other property seasonal',
            'rule_type' => 'seasonal',
            'start_date' => $date->copy()->startOfMonth(),
            'end_date' => $date->copy()->endOfMonth(),
            'adjustment_type' => 'percent',
            'adjustment_value' => -20,
            'priority' => 10,
            'is_enabled' => true,
        ]);

        $this->assertSame(100.0, app(PricingEngine::class)->calculateRateForDate($mainRoom, $date));
    }

    public function test_home_page_advertises_the_linked_property_listing(): void
    {
        [$main, $partner] = $this->linkedProperties();
        $partner->update(['short_description' => 'Listed here too for returning guests.']);
        Room::factory()->create(['property_id' => $main->id, 'status' => 'active']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Also booking via '.$partner->name, false)
            ->assertSee('Listed here too for returning guests.')
            ->assertSee(route('booking.search', ['property_id' => $partner->id]), false);
    }

    public function test_admin_calendar_events_merge_reservations_from_the_linked_property(): void
    {
        [$main, $partner] = $this->linkedProperties();
        $mainRoom = Room::factory()->create(['property_id' => $main->id, 'status' => 'active']);
        $partnerRoom = Room::factory()->create(['property_id' => $partner->id, 'status' => 'active']);
        $guest = Guest::factory()->create(['first_name' => 'Jane', 'last_name' => 'Doe']);

        Reservation::factory()->create([
            'property_id' => $main->id,
            'room_id' => $mainRoom->id,
            'guest_id' => $guest->id,
            'reference' => 'CH-MAIN01',
            'status' => 'confirmed',
            'check_in' => '2026-01-05',
            'check_out' => '2026-01-07',
        ]);
        Reservation::factory()->create([
            'property_id' => $partner->id,
            'room_id' => $partnerRoom->id,
            'guest_id' => $guest->id,
            'reference' => 'CH-LINK01',
            'status' => 'confirmed',
            'check_in' => '2026-01-05',
            'check_out' => '2026-01-08',
        ]);

        $user = User::factory()->create();
        $user->assignRole(Role::findByName('Super Admin'));

        $this->actingAs($user)
            ->getJson(route('admin.calendar.events', [
                'property_id' => $main->id,
                'start' => '2026-01-01',
                'end' => '2026-01-31',
            ]))
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJsonFragment(['reference' => 'CH-MAIN01'])
            ->assertJsonFragment(['reference' => 'CH-LINK01'])
            ->assertJsonFragment(['property_name' => $partner->name]);
    }
}
