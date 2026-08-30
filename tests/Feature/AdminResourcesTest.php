<?php

namespace Tests\Feature;

use App\Models\Guest;
use App\Models\Property;
use App\Models\Room;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminResourcesTest extends TestCase
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

    public function test_super_admin_can_list_guests(): void
    {
        Guest::factory()->create(['first_name' => 'Jane', 'last_name' => 'Doe']);

        $this->actingAs($this->actingAsSuperAdmin())
            ->get(route('admin.guests.index'))
            ->assertOk()
            ->assertSee('Jane');
    }

    public function test_user_without_guest_permission_cannot_list_guests(): void
    {
        $role = Role::create(['name' => 'No Guest Access', 'guard_name' => 'web']);
        $role->givePermissionTo('dashboard.view');
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user)
            ->get(route('admin.guests.index'))
            ->assertForbidden();
    }

    public function test_super_admin_can_create_guest(): void
    {
        $this->actingAs($this->actingAsSuperAdmin())
            ->post(route('admin.guests.store'), [
                'first_name' => 'John',
                'last_name' => 'Smith',
                'email' => 'john@example.com',
                'status' => 'active',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('guests', ['first_name' => 'John', 'email' => 'john@example.com']);
    }

    public function test_super_admin_can_view_reservations_index(): void
    {
        $this->actingAs($this->actingAsSuperAdmin())
            ->get(route('admin.reservations.index'))
            ->assertOk();
    }

    public function test_super_admin_can_view_calendar(): void
    {
        $property = Property::factory()->create();

        $this->actingAs($this->actingAsSuperAdmin())
            ->get(route('admin.calendar', ['property_id' => $property->id]))
            ->assertOk()
            ->assertSee('Custom property calendar')
            ->assertSee('Calendar view')
            ->assertSee('Selected day')
            ->assertSee('Availability')
            ->assertSee('Min Stay')
            ->assertSee('Max Stay')
            ->assertSee('Daily Price')
            ->assertSee('Manual');
    }

    public function test_super_admin_can_view_house_rules_without_a_property_record(): void
    {
        $this->actingAs($this->actingAsSuperAdmin())
            ->get(route('admin.website.house-rules'))
            ->assertOk()
            ->assertSee('No property record exists yet')
            ->assertSee('House Rules')
            ->assertSee('Custom Rules')
            ->assertSee('Save house rules');
    }

    public function test_calendar_events_endpoint_returns_json(): void
    {
        $property = Property::factory()->create();

        $this->actingAs($this->actingAsSuperAdmin())
            ->getJson(route('admin.calendar.events', ['property_id' => $property->id]))
            ->assertOk()
            ->assertJson([]);
    }

    public function test_page_loader_skeleton_renders_in_content_section(): void
    {
        $this->actingAs($this->actingAsSuperAdmin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('id="pageLoaderWrap"', false)
            ->assertSee('id="pageLoader"', false)
            ->assertSee('ch-skeleton', false);
    }

    public function test_calendar_page_opt_out_of_page_loader(): void
    {
        $property = Property::factory()->create();

        $this->actingAs($this->actingAsSuperAdmin())
            ->get(route('admin.calendar', ['property_id' => $property->id]))
            ->assertOk()
            ->assertSee('data-loader-disabled="1"', false)
            ->assertDontSee('id="pageLoader"', false)
            ->assertDontSee('ch-skeleton', false);
    }

    public function test_super_admin_can_store_a_beds24_aligned_calendar_block(): void
    {
        $property = Property::factory()->create();
        $room = Room::factory()->create(['property_id' => $property->id]);

        $this->actingAs($this->actingAsSuperAdmin())
            ->postJson(route('admin.calendar.blocks.store'), [
                'property_id' => $property->id,
                'room_id' => $room->id,
                'start_date' => now()->addDays(3)->toDateString(),
                'end_date' => now()->addDays(5)->toDateString(),
                'title' => 'Beds24 rate change',
                'type' => 'daily_price',
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertDatabaseHas('calendar_blocks', [
            'property_id' => $property->id,
            'room_id' => $room->id,
            'type' => 'daily_price',
            'title' => 'Beds24 rate change',
        ]);
    }

    public function test_super_admin_can_view_pricing(): void
    {
        Property::factory()->create();
        Room::factory()->create();

        $this->actingAs($this->actingAsSuperAdmin())
            ->get(route('admin.pricing.index'))
            ->assertOk()
            ->assertSee('New rule')
            ->assertSee('New override')
            ->assertDontSee('Post to Beds24');
    }

    public function test_super_admin_can_create_pricing_rule(): void
    {
        $property = Property::factory()->create();

        $this->actingAs($this->actingAsSuperAdmin())
            ->post(route('admin.pricing.rules.store'), [
                'property_id' => $property->id,
                'name' => 'Summer high season',
                'rule_type' => 'seasonal',
                'priority' => 4,
                'adjustment_type' => 'multiplier',
                'adjustment_value' => 20,
                'minimum_stay' => 2,
                'max_stay' => 5,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('pricing_rules', ['name' => 'Summer high season', 'adjustment_value' => 20, 'max_stay' => 5]);
    }

    public function test_create_manual_reservation_via_admin(): void
    {
        $room = Room::factory()->create(['base_rate' => 100]);

        $this->actingAs($this->actingAsSuperAdmin())
            ->post(route('admin.reservations.store'), [
                'room_id' => $room->id,
                'check_in' => now()->addDays(5)->toDateString(),
                'check_out' => now()->addDays(8)->toDateString(),
                'guests_count' => 2,
                'guest_first_name' => 'Alice',
                'guest_last_name' => 'Brown',
                'guest_email' => 'alice@example.com',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('reservations', ['source' => 'manual', 'status' => 'confirmed']);
    }

    public function test_super_admin_can_view_properties_index(): void
    {
        Property::factory()->create(['name' => 'Maple Cottage']);

        $this->actingAs($this->actingAsSuperAdmin())
            ->get(route('admin.properties.index'))
            ->assertOk()
            ->assertSee('Maple Cottage');
    }

    public function test_super_admin_can_view_rooms_index_for_a_property(): void
    {
        $property = Property::factory()->create();
        $room = Room::factory()->create(['property_id' => $property->id, 'name' => 'Garden Room']);

        $this->actingAs($this->actingAsSuperAdmin())
            ->get(route('admin.rooms.index', $property))
            ->assertOk()
            ->assertSee('Garden Room');
    }

    public function test_super_admin_can_view_room_management_window(): void
    {
        $property = Property::factory()->create(['name' => 'Maple Cottage']);
        Room::factory()->create(['property_id' => $property->id, 'name' => 'Garden Room']);
        Room::factory()->create(['property_id' => $property->id, 'name' => 'Attic Room']);

        $this->actingAs($this->actingAsSuperAdmin())
            ->get(route('admin.rooms.manage'))
            ->assertOk()
            ->assertSee('Garden Room')
            ->assertSee('Attic Room')
            ->assertSee('Maple Cottage');
    }

    public function test_room_management_window_filters_by_status(): void
    {
        $property = Property::factory()->create();
        Room::factory()->create(['property_id' => $property->id, 'name' => 'Active Room', 'status' => 'active']);
        Room::factory()->create(['property_id' => $property->id, 'name' => 'Maintenance Room', 'status' => 'maintenance']);

        $this->actingAs($this->actingAsSuperAdmin())
            ->get(route('admin.rooms.manage', ['status' => 'maintenance']))
            ->assertOk()
            ->assertSee('Maintenance Room')
            ->assertDontSee('Active Room');
    }

    public function test_room_edit_route_resolves(): void
    {
        $property = Property::factory()->create();
        $room = Room::factory()->create(['property_id' => $property->id]);

        $this->actingAs($this->actingAsSuperAdmin())
            ->get(route('admin.rooms.edit', $room))
            ->assertOk();
    }

    public function test_super_admin_can_create_property(): void
    {
        $this->actingAs($this->actingAsSuperAdmin())
            ->post(route('admin.properties.store'), [
                'name' => 'Sea View House',
                'status' => 'active',
                'city' => 'Brighton',
                'currency' => 'GBP',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('properties', ['name' => 'Sea View House']);
    }

    public function test_super_admin_can_create_room_with_image(): void
    {
        Storage::fake('public');
        $property = Property::factory()->create();

        $this->actingAs($this->actingAsSuperAdmin())
            ->post(route('admin.rooms.store', $property), [
                'name' => 'Rose Suite',
                'status' => 'active',
                'base_rate' => 150,
                'images' => [
                    UploadedFile::fake()->image('room.jpg'),
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('rooms', ['name' => 'Rose Suite']);
        $this->assertDatabaseCount('room_images', 1);
    }
}
