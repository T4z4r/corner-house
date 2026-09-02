<?php

namespace Tests\Feature;

use App\Models\AddOn;
use App\Models\CalendarBlock;
use App\Models\ChannelAccount;
use App\Models\ChannelMapping;
use App\Models\FoodAndDrink;
use App\Models\Guest;
use App\Models\KnowledgeBaseArticle;
use App\Models\PlacesOfInterest;
use App\Models\PricingRule;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
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

    public function test_super_admin_can_update_a_calendar_block(): void
    {
        $property = Property::factory()->create();
        $room = Room::factory()->create(['property_id' => $property->id]);
        $block = CalendarBlock::create([
            'property_id' => $property->id,
            'room_id' => $room->id,
            'type' => 'daily_price',
            'value' => 150,
            'start_date' => now()->addDays(3)->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
        ]);

        $this->actingAs($this->actingAsSuperAdmin())
            ->postJson(route('admin.calendar.blocks.update', $block), [
                'value' => 220,
                'title' => 'Updated rate',
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertDatabaseHas('calendar_blocks', [
            'id' => $block->id,
            'value' => 220,
            'title' => 'Updated rate',
        ]);
    }

    public function test_super_admin_can_toggle_a_calendar_block(): void
    {
        $property = Property::factory()->create();
        $room = Room::factory()->create(['property_id' => $property->id]);
        $block = CalendarBlock::create([
            'property_id' => $property->id,
            'room_id' => $room->id,
            'type' => 'availability',
            'start_date' => now()->addDays(3)->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
        ]);

        $this->actingAs($this->actingAsSuperAdmin())
            ->postJson(route('admin.calendar.blocks.toggle', $block))
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertDatabaseHas('calendar_blocks', [
            'id' => $block->id,
            'is_active' => false,
        ]);
    }

    public function test_super_admin_can_delete_a_calendar_block(): void
    {
        $property = Property::factory()->create();
        $room = Room::factory()->create(['property_id' => $property->id]);
        $block = CalendarBlock::create([
            'property_id' => $property->id,
            'room_id' => $room->id,
            'type' => 'availability',
            'start_date' => now()->addDays(3)->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
        ]);

        $this->actingAs($this->actingAsSuperAdmin())
            ->deleteJson(route('admin.calendar.blocks.destroy', $block))
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertDatabaseMissing('calendar_blocks', ['id' => $block->id]);
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
            ->assertSee('Generate seasonal pricing')
            ->assertDontSee('Post to Beds24');
    }

    public function test_super_admin_can_edit_a_pricing_rule_via_modal(): void
    {
        $property = Property::factory()->create();
        $rule = PricingRule::create([
            'property_id' => $property->id,
            'name' => 'Summer high season',
            'rule_type' => 'seasonal',
            'priority' => 4,
            'adjustment_type' => 'percent',
            'adjustment_value' => 15,
            'minimum_stay' => 2,
            'max_stay' => 5,
            'is_enabled' => true,
        ]);

        $this->actingAs($this->actingAsSuperAdmin())
            ->get(route('admin.pricing.index'))
            ->assertOk()
            ->assertSee('Summer high season')
            ->assertSee('id="editRule'.$rule->id.'"', false)
            ->assertSee($rule->adjustment_value, false);

        $this->actingAs($this->actingAsSuperAdmin())
            ->put(route('admin.pricing.rules.update', $rule), [
                'name' => 'Summer high season',
                'priority' => 5,
                'adjustment_type' => 'percent',
                'adjustment_value' => 20,
                'minimum_stay' => 2,
                'max_stay' => 5,
                'occupancy_threshold' => null,
                'days_before_checkin' => null,
                'apply_weekends_only' => false,
                'is_enabled' => true,
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Pricing rule updated.');

        $this->assertDatabaseHas('pricing_rules', [
            'id' => $rule->id,
            'name' => 'Summer high season',
            'priority' => 5,
            'adjustment_value' => 20.0,
        ]);
    }

    public function test_super_admin_can_generate_ai_seasonal_pricing_rules(): void
    {
        $this->seed(SettingsSeeder::class);
        Setting::query()->where('key', 'openai_api_key')->update(['value' => Setting::encryptSecret('sk-test-openai')]);
        cache()->forget('settings.all');

        $property = Property::factory()->create();

        Http::fake([
            'api.open-meteo.com/*' => Http::response([
                'daily' => [
                    'time' => [now()->toDateString()],
                    'temperature_2m_max' => [23.4],
                    'temperature_2m_min' => [14.1],
                    'precipitation_probability_max' => [15],
                    'weathercode' => [1],
                ],
            ], 200),
            'api.openai.com/*' => Http::response([
                'output_text' => json_encode([
                    'summary' => 'Seasonal demand is healthy and should be nudged up slightly.',
                    'rules' => [
                        [
                            'generation_key' => 'late-summer-uplift',
                            'name' => 'Late summer uplift',
                            'rule_type' => 'seasonal',
                            'start_date' => now()->addWeek()->toDateString(),
                            'end_date' => now()->addWeeks(6)->toDateString(),
                            'priority' => 5,
                            'adjustment_type' => 'percent',
                            'adjustment_value' => 12,
                            'minimum_stay' => 2,
                            'max_stay' => 5,
                            'occupancy_threshold' => null,
                            'days_before_checkin' => null,
                            'apply_weekends_only' => false,
                            'reasoning' => 'Tourism demand is expected to rise.',
                        ],
                    ],
                ], JSON_THROW_ON_ERROR),
            ], 200),
        ]);

        $this->actingAs($this->actingAsSuperAdmin())
            ->post(route('admin.pricing.ai.generate'), [
                'property_id' => $property->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('pricing_rules', [
            'property_id' => $property->id,
            'name' => 'Late summer uplift',
            'generated_by_ai' => true,
            'ai_generation_key' => 'late-summer-uplift',
        ]);
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

    public function test_guest_manager_can_store_scheduled_event_articles(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole(Role::findByName('Guest Manager'));

        $this->actingAs($user)
            ->post(route('admin.chatbot.articles.store'), [
                'category' => 'local-event',
                'title' => 'Harvest supper club',
                'content' => 'An autumn tasting menu with live music.',
                'status' => 'active',
                'priority' => 1,
                'starts_at' => '2026-09-10',
                'ends_at' => '2026-09-12',
                'show_on_website' => '1',
            ])
            ->assertRedirect();

        $this->assertTrue(
            KnowledgeBaseArticle::query()
                ->where('title', 'Harvest supper club')
                ->whereDate('starts_at', '2026-09-10')
                ->whereDate('ends_at', '2026-09-12')
                ->where('show_on_website', true)
                ->exists(),
        );
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

    public function test_super_admin_can_view_the_reservation_edit_form(): void
    {
        $reservation = Reservation::factory()->create([
            'guests_count' => 2,
        ]);

        $this->actingAs($this->actingAsSuperAdmin())
            ->get(route('admin.reservations.edit', $reservation))
            ->assertOk()
            ->assertSee('Edit Reservation')
            ->assertSee($reservation->room->name);
    }

    public function test_super_admin_can_update_a_reservation(): void
    {
        $reservation = Reservation::factory()->create([
            'check_in' => now()->addDays(10)->toDateString(),
            'check_out' => now()->addDays(12)->toDateString(),
            'guests_count' => 2,
            'notes' => 'Original note',
        ]);

        $newCheckIn = now()->addDays(20)->toDateString();
        $newCheckOut = now()->addDays(23)->toDateString();

        $this->actingAs($this->actingAsSuperAdmin())
            ->put(route('admin.reservations.update', $reservation), [
                'room_id' => $reservation->room_id,
                'check_in' => $newCheckIn,
                'check_out' => $newCheckOut,
                'guests_count' => 3,
                'guest_email' => 'updated@example.com',
                'guest_first_name' => 'Alice',
                'guest_last_name' => 'Brown',
                'notes' => 'Updated note',
            ])
            ->assertRedirect(route('admin.reservations.show', $reservation))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'guests_count' => 3,
            'notes' => 'Updated note',
        ]);

        $updated = $reservation->fresh();
        $this->assertSame($newCheckIn, $updated->check_in->format('Y-m-d'));
        $this->assertSame($newCheckOut, $updated->check_out->format('Y-m-d'));
    }

    public function test_super_admin_can_delete_an_unpaid_reservation(): void
    {
        $reservation = Reservation::factory()->create([
            'payment_status' => 'unpaid',
            'paid_amount' => 0,
        ]);

        $this->actingAs($this->actingAsSuperAdmin())
            ->delete(route('admin.reservations.destroy', $reservation))
            ->assertRedirect(route('admin.reservations.index'));

        $this->assertDatabaseMissing('reservations', ['id' => $reservation->id]);
    }

    public function test_super_admin_cannot_delete_a_paid_reservation(): void
    {
        $reservation = Reservation::factory()->create([
            'payment_status' => 'paid',
            'paid_amount' => 100,
        ]);

        $this->actingAs($this->actingAsSuperAdmin())
            ->delete(route('admin.reservations.destroy', $reservation))
            ->assertRedirect()
            ->assertSessionHasErrors('error');

        $this->assertDatabaseHas('reservations', ['id' => $reservation->id]);
    }

    public function test_admin_without_reservation_update_permission_cannot_edit(): void
    {
        $role = Role::create(['name' => 'Booking Editor', 'guard_name' => 'web']);
        $role->givePermissionTo('reservations.view');
        $user = User::factory()->create();
        $user->assignRole($role);

        $reservation = Reservation::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.reservations.edit', $reservation))
            ->assertForbidden();
    }

    public function test_super_admin_can_resync_a_booking_to_beds24_from_the_reservation_page(): void
    {
        $account = ChannelAccount::factory()->create([
            'provider' => 'beds24',
            'status' => 'active',
            'credentials' => [
                'refresh_token' => 'refresh-token',
                'access_token' => 'access-token',
                'access_token_expires_at' => now()->addHour()->toIso8601String(),
            ],
        ]);
        $property = Property::factory()->create();
        $room = Room::factory()->create([
            'property_id' => $property->id,
            'status' => 'active',
        ]);
        ChannelMapping::create([
            'channel_account_id' => $account->id,
            'provider' => 'beds24',
            'property_id' => $property->id,
            'room_id' => $room->id,
            'external_property_id' => '2001',
            'external_room_id' => '77',
            'status' => 'active',
        ]);
        $reservation = Reservation::factory()->create([
            'property_id' => $property->id,
            'room_id' => $room->id,
            'status' => 'confirmed',
            'external_channel' => null,
            'external_booking_id' => null,
        ]);

        Http::fake([
            '*bookings*' => Http::response([
                'data' => [[
                    'id' => 8123,
                ]],
            ], 200),
        ]);

        $this->actingAs($this->actingAsSuperAdmin())
            ->get(route('admin.reservations.show', $reservation))
            ->assertOk()
            ->assertSee('Resync booking to Beds24');

        $this->actingAs($this->actingAsSuperAdmin())
            ->post(route('admin.channels.bookings.publish', $reservation))
            ->assertRedirect()
            ->assertSessionHas('status', 'Booking posted to Beds24.');

        Http::assertSent(fn ($request) => str_contains($request->url(), 'bookings')
            && (int) ($request->data()[0]['roomId'] ?? 0) === 77
            && (string) ($request->data()[0]['status'] ?? '') === 'confirmed');

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'external_channel' => 'beds24',
            'external_booking_id' => '8123',
            'sync_status' => 'synced',
        ]);
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

    public function test_super_admin_can_view_food_and_drink_index_with_pagination(): void
    {
        for ($i = 1; $i <= 21; $i++) {
            FoodAndDrink::create([
                'name' => 'Local Bistro '.$i,
                'slug' => 'local-bistro-'.$i,
                'category' => 'restaurant',
                'is_active' => true,
                'sort_order' => $i,
            ]);
        }

        $this->actingAs($this->actingAsSuperAdmin())
            ->get(route('admin.food-drink.index'))
            ->assertOk()
            ->assertSee('Local Bistro 1')
            ->assertSee('Next');
    }

    public function test_super_admin_can_toggle_food_and_drink_featured_status(): void
    {
        $item = FoodAndDrink::create([
            'name' => 'Signature Dinner',
            'slug' => 'signature-dinner',
            'category' => 'restaurant',
            'is_featured' => false,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->actingAs($this->actingAsSuperAdmin())
            ->post(route('admin.food-drink.toggle-featured', $item))
            ->assertRedirect();

        $this->assertTrue($item->refresh()->is_featured);
    }

    public function test_super_admin_can_upload_food_and_drink_image(): void
    {
        Storage::fake('public');

        $uploaded = $this->actingAs($this->actingAsSuperAdmin())
            ->post(route('admin.food-drink.upload-image'), [
                'file' => UploadedFile::fake()->image('dish.jpg', 600, 400),
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonStructure(['path', 'url']);

        $path = $uploaded->json('path');
        $this->assertStringStartsWith('food-drink/', $path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_food_and_drink_image_upload_rejects_non_image(): void
    {
        Storage::fake('public');

        $this->actingAs($this->actingAsSuperAdmin())
            ->post(route('admin.food-drink.upload-image'), [
                'file' => UploadedFile::fake()->create('notes.txt', 100),
            ])
            ->assertSessionHasErrors('file');
    }

    public function test_super_admin_can_delete_uploaded_food_and_drink_image(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('food-drink/unused.jpg', 'image');

        $this->actingAs($this->actingAsSuperAdmin())
            ->post(route('admin.food-drink.delete-uploaded-image'), [
                'path' => 'food-drink/unused.jpg',
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);

        Storage::disk('public')->assertMissing('food-drink/unused.jpg');
    }

    public function test_food_and_drink_image_delete_rejects_foreign_paths(): void
    {
        Storage::fake('public');

        $this->actingAs($this->actingAsSuperAdmin())
            ->post(route('admin.food-drink.delete-uploaded-image'), [
                'path' => 'places/other.jpg',
            ])
            ->assertStatus(422);
    }

    public function test_super_admin_can_upload_places_image(): void
    {
        Storage::fake('public');

        $uploaded = $this->actingAs($this->actingAsSuperAdmin())
            ->post(route('admin.places.upload-image'), [
                'file' => UploadedFile::fake()->image('castle.jpg', 800, 600),
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonStructure(['path', 'url']);

        $path = $uploaded->json('path');
        $this->assertStringStartsWith('places/', $path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_super_admin_can_delete_uploaded_places_image(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('places/unused.jpg', 'image');

        $this->actingAs($this->actingAsSuperAdmin())
            ->post(route('admin.places.delete-uploaded-image'), [
                'path' => 'places/unused.jpg',
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);

        Storage::disk('public')->assertMissing('places/unused.jpg');
    }

    public function test_super_admin_can_view_addons_index_with_pagination(): void
    {
        for ($i = 1; $i <= 21; $i++) {
            AddOn::create([
                'name' => 'Breakfast Pack '.$i,
                'slug' => 'breakfast-pack-'.$i,
                'category' => 'food',
                'price' => 15,
                'is_active' => true,
                'sort_order' => $i,
            ]);
        }

        $this->actingAs($this->actingAsSuperAdmin())
            ->get(route('admin.addons.index'))
            ->assertOk()
            ->assertSee('Breakfast Pack 1')
            ->assertSee('Next');
    }

    public function test_super_admin_can_view_places_index_with_pagination(): void
    {
        for ($i = 1; $i <= 21; $i++) {
            PlacesOfInterest::create([
                'name' => 'Attraction '.$i,
                'slug' => 'attraction-'.$i,
                'category' => 'attraction',
                'is_active' => true,
                'sort_order' => $i,
            ]);
        }

        $this->actingAs($this->actingAsSuperAdmin())
            ->get(route('admin.places.index'))
            ->assertOk()
            ->assertSee('Attraction 1')
            ->assertSee('Next');
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
