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

    public function test_calendar_prices_redirect_guests_to_login(): void
    {
        $this->getJson(route('admin.calendar.prices'))
            ->assertUnauthorized();
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
