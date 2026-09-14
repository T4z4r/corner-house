<?php

namespace Tests\Feature;

use App\Models\PricingOverride;
use App\Models\Property;
use App\Models\Room;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CalendarPricingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function actingAsSuperAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findByName('Super Admin'));

        return $user;
    }

    public function test_calendar_prices_return_the_daily_price_for_a_room(): void
    {
        $property = Property::factory()->create();
        $room = Room::factory()->create([
            'property_id' => $property->id,
            'name' => 'Oak Suite',
            'base_rate' => 650,
        ]);

        $this->actingAs($this->actingAsSuperAdmin())
            ->getJson(route('admin.calendar.prices', [
                'property_id' => $property->id,
                'room_id' => $room->id,
                'start' => '2026-01-05',
                'end' => '2026-01-07',
            ]))
            ->assertOk()
            ->assertJsonCount(1, 'prices.2026-01-05')
            ->assertJsonPath('prices.2026-01-05.0.room_id', $room->id)
            ->assertJsonPath('prices.2026-01-05.0.room_name', 'Oak Suite')
            ->assertJsonPath('prices.2026-01-05.0.price', 650)
            ->assertJsonPath('prices.2026-01-05.0.source', 'base_rate')
            ->assertJsonPath('prices.2026-01-05.0.category', 'weekday')
            ->assertJsonPath('prices.2026-01-06.0.price', 650);
    }

    public function test_no_room_selected_returns_prices_for_all_active_rooms_of_the_property(): void
    {
        $property = Property::factory()->create();
        $first = Room::factory()->create(['property_id' => $property->id, 'name' => 'Oak Suite', 'base_rate' => 500]);
        $second = Room::factory()->create(['property_id' => $property->id, 'name' => 'Garden Room', 'base_rate' => 700]);

        $this->actingAs($this->actingAsSuperAdmin())
            ->getJson(route('admin.calendar.prices', [
                'property_id' => $property->id,
                'start' => '2026-02-09',
                'end' => '2026-02-09',
            ]))
            ->assertOk()
            ->assertJsonCount(2, 'prices.2026-02-09');

        $response = $this->actingAs($this->actingAsSuperAdmin())
            ->getJson(route('admin.calendar.prices', [
                'property_id' => $property->id,
                'start' => '2026-02-09',
                'end' => '2026-02-09',
            ]))
            ->assertOk();

        $roomIds = collect($response->json('prices.2026-02-09'))->pluck('room_id')->all();
        sort($roomIds);
        $this->assertSame([$first->id, $second->id], $roomIds);
    }

    public function test_calendar_prices_exclude_inactive_rooms(): void
    {
        $property = Property::factory()->create();
        Room::factory()->create(['property_id' => $property->id, 'name' => 'Open', 'base_rate' => 500]);
        Room::factory()->create(['property_id' => $property->id, 'name' => 'Closed', 'base_rate' => 700, 'status' => 'inactive']);

        $this->actingAs($this->actingAsSuperAdmin())
            ->getJson(route('admin.calendar.prices', [
                'property_id' => $property->id,
                'start' => '2026-02-09',
                'end' => '2026-02-09',
            ]))
            ->assertOk()
            ->assertJsonCount(1, 'prices.2026-02-09')
            ->assertJsonPath('prices.2026-02-09.0.room_name', 'Open');
    }

    public function test_calendar_prices_apply_a_manual_pricing_override(): void
    {
        $property = Property::factory()->create();
        $room = Room::factory()->create([
            'property_id' => $property->id,
            'name' => 'Oak Suite',
            'base_rate' => 650,
        ]);

        PricingOverride::query()->create([
            'room_id' => $room->id,
            'start_date' => '2026-01-06',
            'end_date' => '2026-01-06',
            'rate' => 999,
            'is_enabled' => true,
        ]);

        $this->actingAs($this->actingAsSuperAdmin())
            ->getJson(route('admin.calendar.prices', [
                'property_id' => $property->id,
                'room_id' => $room->id,
                'start' => '2026-01-05',
                'end' => '2026-01-07',
            ]))
            ->assertOk()
            ->assertJsonPath('prices.2026-01-05.0.price', 650)
            ->assertJsonPath('prices.2026-01-05.0.source', 'base_rate')
            ->assertJsonPath('prices.2026-01-06.0.price', 999)
            ->assertJsonPath('prices.2026-01-06.0.source', 'override');
    }

    public function test_calendar_price_can_be_set_from_the_calendar(): void
    {
        $property = Property::factory()->create();
        $room = Room::factory()->create([
            'property_id' => $property->id,
            'name' => 'Oak Suite',
            'base_rate' => 650,
        ]);

        $this->actingAs($this->actingAsSuperAdmin())
            ->postJson(route('admin.calendar.prices.store'), [
                'property_id' => $property->id,
                'room_id' => $room->id,
                'start_date' => '2026-01-06',
                'end_date' => '2026-01-07',
                'rate' => 999,
                'notes' => 'Peak weekend',
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertSame(1, PricingOverride::query()->count());

        $override = PricingOverride::query()->firstOrFail();
        $this->assertSame('2026-01-06', $override->start_date->toDateString());
        $this->assertSame('2026-01-07', $override->end_date->toDateString());
        $this->assertSame(999.0, (float) $override->rate);

        $this->actingAs($this->actingAsSuperAdmin())
            ->getJson(route('admin.calendar.prices', [
                'property_id' => $property->id,
                'room_id' => $room->id,
                'start' => '2026-01-05',
                'end' => '2026-01-08',
            ]))
            ->assertOk()
            ->assertJsonPath('prices.2026-01-06.0.price', 999)
            ->assertJsonPath('prices.2026-01-06.0.source', 'override')
            ->assertJsonPath('prices.2026-01-07.0.price', 999)
            ->assertJsonPath('prices.2026-01-08.0.price', 650);
    }

    public function test_setting_a_calendar_price_replaces_overlapping_overrides(): void
    {
        $property = Property::factory()->create();
        $room = Room::factory()->create([
            'property_id' => $property->id,
            'name' => 'Oak Suite',
            'base_rate' => 650,
        ]);

        PricingOverride::query()->create([
            'room_id' => $room->id,
            'start_date' => '2026-01-05',
            'end_date' => '2026-01-07',
            'rate' => 800,
            'is_enabled' => true,
        ]);

        $this->actingAs($this->actingAsSuperAdmin())
            ->postJson(route('admin.calendar.prices.store'), [
                'property_id' => $property->id,
                'room_id' => $room->id,
                'start_date' => '2026-01-06',
                'end_date' => '2026-01-06',
                'rate' => 999,
            ])
            ->assertOk();

        $this->assertSame(1, PricingOverride::query()->count());
        $this->assertSame(999.0, (float) PricingOverride::query()->firstOrFail()->rate);

        $this->actingAs($this->actingAsSuperAdmin())
            ->getJson(route('admin.calendar.prices', [
                'property_id' => $property->id,
                'room_id' => $room->id,
                'start' => '2026-01-05',
                'end' => '2026-01-07',
            ]))
            ->assertOk()
            ->assertJsonPath('prices.2026-01-05.0.price', 650)
            ->assertJsonPath('prices.2026-01-06.0.price', 999)
            ->assertJsonPath('prices.2026-01-07.0.price', 650);
    }

    public function test_calendar_price_can_be_removed(): void
    {
        $property = Property::factory()->create();
        $room = Room::factory()->create([
            'property_id' => $property->id,
            'name' => 'Oak Suite',
            'base_rate' => 650,
        ]);

        $override = PricingOverride::query()->create([
            'room_id' => $room->id,
            'start_date' => '2026-01-06',
            'end_date' => '2026-01-06',
            'rate' => 999,
            'is_enabled' => true,
        ]);

        $this->actingAs($this->actingAsSuperAdmin())
            ->deleteJson(route('admin.calendar.prices.destroy', $override))
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertSame(0, PricingOverride::query()->count());
    }

    public function test_calendar_price_store_requires_calendar_manage_permission(): void
    {
        $property = Property::factory()->create();
        $room = Room::factory()->create(['property_id' => $property->id]);

        $role = Role::create(['name' => 'Calendar Viewer', 'guard_name' => 'web']);
        $role->givePermissionTo('calendar.view');
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user)
            ->postJson(route('admin.calendar.prices.store'), [
                'property_id' => $property->id,
                'room_id' => $room->id,
                'start_date' => '2026-01-06',
                'end_date' => '2026-01-06',
                'rate' => 999,
            ])
            ->assertForbidden();
    }

    public function test_calendar_price_store_validates_input(): void
    {
        $property = Property::factory()->create();
        $room = Room::factory()->create(['property_id' => $property->id]);

        $this->actingAs($this->actingAsSuperAdmin())
            ->postJson(route('admin.calendar.prices.store'), [
                'property_id' => $property->id,
                'room_id' => $room->id,
                'start_date' => '2026-01-07',
                'end_date' => '2026-01-06',
                'rate' => null,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['rate', 'end_date']);
    }

    public function test_calendar_prices_redirect_guests_to_login(): void
    {
        $this->getJson(route('admin.calendar.prices'))
            ->assertUnauthorized();
    }

    public function test_admin_calendar_page_renders_when_authorized(): void
    {
        $this->actingAs($this->actingAsSuperAdmin())
            ->get(route('admin.calendar'))
            ->assertOk()
            ->assertSee('Calendar')
            ->assertSee('priceModal', false);
    }

    public function test_calendar_prices_require_the_calendar_view_permission(): void
    {
        $role = Role::create(['name' => 'No Calendar Access', 'guard_name' => 'web']);
        $role->givePermissionTo('dashboard.view');
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user)
            ->getJson(route('admin.calendar.prices'))
            ->assertForbidden();
    }
}
