<?php

namespace Tests\Feature;

use App\Jobs\PushBeds24RatesJob;
use App\Jobs\SyncBeds24BookingsJob;
use App\Models\ChannelAccount;
use App\Models\ChannelMapping;
use App\Models\Property;
use App\Models\Room;
use App\Models\Setting;
use App\Models\User;
use App\Services\Beds24\Beds24AlertService;
use App\Services\Beds24\Beds24MessageService;
use App\Services\Beds24\Beds24SyncService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class Beds24SyncReliabilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        config(['services.beds24.refresh_token' => null]);

        Setting::updateOrCreate(['key' => 'min_price_weekday'], ['value' => '0', 'group' => 'booking', 'label' => 'Min weekday', 'cast' => 'decimal:2']);
        Setting::updateOrCreate(['key' => 'min_price_weekend'], ['value' => '0', 'group' => 'booking', 'label' => 'Min weekend', 'cast' => 'decimal:2']);
        Setting::updateOrCreate(['key' => 'cleaning_fee'], ['value' => '0', 'group' => 'booking', 'label' => 'Cleaning', 'cast' => 'decimal:2']);
    }

    private function beds24Account(string $status = 'active'): ChannelAccount
    {
        return ChannelAccount::factory()->create([
            'provider' => 'beds24',
            'status' => $status,
            'credentials' => [
                'refresh_token' => 'refresh-token',
                'access_token' => 'access-1',
                'access_token_expires_at' => now()->addHour()->toIso8601String(),
            ],
        ]);
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findByName('Super Admin'));

        return $user;
    }

    private function catalog(): void
    {
        Http::fake([
            '*properties*' => Http::response([
                'data' => [[
                    'id' => 2001,
                    'name' => 'Corner House B24',
                    'city' => 'Towcester',
                    'postcode' => 'NN12 1AA',
                    'country' => 'GB',
                    'currency' => 'GBP',
                    'roomTypes' => [[
                        'id' => 77,
                        'name' => 'Oak Suite',
                        'maxPeople' => 3,
                        'minStay' => 2,
                    ]],
                ]],
            ], 200),
        ]);
    }

    public function test_scheduled_sync_retries_an_account_stuck_in_error_status(): void
    {
        $account = $this->beds24Account('error');
        $checkIn = now()->addDays(20)->toDateString();
        $checkOut = now()->addDays(23)->toDateString();

        $this->catalog();
        Http::fake([
            '*bookings*' => Http::response([
                'data' => [[
                    'id' => 9001,
                    'propertyId' => 2001,
                    'roomId' => 77,
                    'arrival' => $checkIn,
                    'departure' => $checkOut,
                    'firstName' => 'Lee',
                    'lastName' => 'Guest',
                    'email' => 'lee@example.com',
                    'numAdult' => 2,
                    'status' => 'confirmed',
                    'channel' => 'airbnb',
                    'price' => 360,
                ]],
            ], 200),
            '*inventory/rooms/calendar*' => Http::response(['data' => []], 200),
        ]);

        app(SyncBeds24BookingsJob::class)->handle(
            app(Beds24SyncService::class),
            app(Beds24AlertService::class),
        );

        $this->assertDatabaseHas('reservations', [
            'external_channel' => 'beds24',
            'external_booking_id' => '9001',
            'source' => 'airbnb',
            'status' => 'confirmed',
        ]);

        $account->refresh();
        $this->assertSame('active', $account->status);
        $this->assertNull($account->last_error);
        $this->assertSame('ok', $account->settings['last_sync_health']);
    }

    public function test_scheduled_sync_skips_accounts_without_credentials(): void
    {
        ChannelAccount::factory()->create([
            'provider' => 'beds24',
            'status' => 'error',
            'credentials' => [],
        ]);

        Http::fake();

        app(SyncBeds24BookingsJob::class)->handle(
            app(Beds24SyncService::class),
            app(Beds24AlertService::class),
        );

        Http::assertNothingSent();
        $this->assertDatabaseCount('reservations', 0);
    }

    public function test_a_single_failing_endpoint_does_not_break_the_sync_and_recovers_automatically(): void
    {
        $account = $this->beds24Account();
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

        $checkIn = now()->addDays(20)->toDateString();
        $checkOut = now()->addDays(23)->toDateString();

        $propertiesCalls = 0;

        Http::fake([
            '*properties*' => function () use (&$propertiesCalls) {
                $propertiesCalls++;

                return $propertiesCalls <= 2
                    ? Http::response(['success' => false, 'error' => 'Server error'], 500)
                    : Http::response(['data' => []], 200);
            },
            '*bookings*' => Http::response([
                'data' => [[
                    'id' => 9001,
                    'propertyId' => 2001,
                    'roomId' => 77,
                    'arrival' => $checkIn,
                    'departure' => $checkOut,
                    'firstName' => 'Lee',
                    'lastName' => 'Guest',
                    'email' => 'lee@example.com',
                    'numAdult' => 2,
                    'status' => 'confirmed',
                    'channel' => 'airbnb',
                    'price' => 360,
                ]],
            ], 200),
            '*inventory/rooms/calendar*' => Http::response(['data' => []], 200),
        ]);

        $counts = app(Beds24SyncService::class)->synchronize($account);

        $this->assertSame(1, $counts['bookings']);
        $this->assertCount(1, $counts['errors']);
        $this->assertStringContainsString('catalog: HTTP request returned status code 500', $counts['errors'][0]);

        $account->refresh();
        $this->assertSame('error', $account->status);
        $this->assertSame('partial', $account->settings['last_sync_health']);
        $this->assertTrue($account->isSyncEligible(), 'An account with a failing endpoint must remain eligible for the next scheduled run.');

        app(Beds24SyncService::class)->synchronize($account);

        $account->refresh();
        $this->assertSame('active', $account->status);
        $this->assertNull($account->last_error);
        $this->assertSame('ok', $account->settings['last_sync_health']);
    }

    public function test_message_sync_processes_error_status_accounts_with_credentials(): void
    {
        $account = $this->beds24Account('error');

        Http::fake([
            '*bookings/messages*' => Http::response([
                'data' => [[
                    'id' => 'm-100',
                    'bookingId' => '92672145',
                    'message' => 'Hello from the guest',
                    'time' => now()->subMinutes(5)->toISOString(),
                    'source' => 'Guest',
                    'read' => false,
                    'authorOwnerId' => null,
                ]],
            ], 200),
        ]);

        $summary = app(Beds24MessageService::class)->syncAll();

        $this->assertSame(1, $summary['created']);
        $this->assertDatabaseHas('communications', [
            'provider_message_id' => 'm-100-92672145',
            'status' => 'pending',
        ]);

        $account->refresh();
        $this->assertNotNull($account->last_message_synced_at);
        $this->assertSame('success', $account->last_message_sync_status);
    }

    public function test_manual_channel_sync_runs_bookings_and_messages_jobs_immediately(): void
    {
        $account = $this->beds24Account();
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

        Http::fake([
            '*properties*' => Http::response(['data' => []], 200),
            '*bookings*' => Http::response([
                'data' => [[
                    'id' => 9011,
                    'roomId' => 77,
                    'arrival' => now()->addDays(10)->toDateString(),
                    'departure' => now()->addDays(13)->toDateString(),
                    'status' => 'confirmed',
                    'numAdult' => 2,
                ]],
            ], 200),
            '*inventory/rooms/calendar*' => Http::response(['data' => []], 200),
        ]);

        $this->actingAs($this->superAdmin())
            ->post(route('admin.channels.sync'))
            ->assertRedirect()
            ->assertSessionHas('status', 'Beds24 sync completed. Properties, rooms, bookings, calendar and messages are now aligned.');

        $this->assertDatabaseHas('reservations', [
            'external_channel' => 'beds24',
            'external_booking_id' => '9011',
            'room_id' => $room->id,
        ]);
    }

    public function test_push_rates_job_is_disabled_and_never_overrides_prices(): void
    {
        $account = $this->beds24Account();
        $property = Property::factory()->create();
        $room = Room::factory()->create(['property_id' => $property->id, 'status' => 'active']);
        ChannelMapping::create([
            'channel_account_id' => $account->id,
            'provider' => 'beds24',
            'property_id' => $property->id,
            'room_id' => $room->id,
            'external_property_id' => '2001',
            'external_room_id' => '77',
            'status' => 'active',
        ]);

        Http::fake();

        app(PushBeds24RatesJob::class)->handle();

        Http::assertNothingSent();
    }

    public function test_channel_account_eligibility_does_not_depend_on_status(): void
    {
        $this->assertTrue($this->beds24Account('error')->isSyncEligible());
        $this->assertTrue($this->beds24Account('active')->isSyncEligible());

        $unconfigured = ChannelAccount::factory()->create([
            'provider' => 'beds24',
            'status' => 'inactive',
            'credentials' => [],
        ]);
        $this->assertFalse($unconfigured->isSyncEligible());
    }
}
