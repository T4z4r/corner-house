<?php

namespace Tests\Feature;

use App\Jobs\SyncBeds24BookingsJob;
use App\Models\ChannelAccount;
use App\Models\ChannelSyncLog;
use App\Models\Setting;
use App\Models\User;
use App\Services\Beds24\Beds24AlertService;
use App\Services\Beds24\Beds24SyncService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ChannelSyncProgressTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        config(['services.beds24.refresh_token' => null]);

        Setting::firstOrCreate(['key' => 'min_price_weekday'], ['value' => '0', 'group' => 'booking', 'label' => 'Min weekday', 'cast' => 'decimal:2']);
        Setting::firstOrCreate(['key' => 'min_price_weekend'], ['value' => '0', 'group' => 'booking', 'label' => 'Min weekend', 'cast' => 'decimal:2']);
        Setting::firstOrCreate(['key' => 'cleaning_fee'], ['value' => '0', 'group' => 'booking', 'label' => 'Cleaning', 'cast' => 'decimal:2']);
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findByName('Super Admin'));

        return $user;
    }

    private function fullSyncFakes(): void
    {
        $checkIn = now()->addDays(20)->toDateString();
        $checkOut = now()->addDays(23)->toDateString();

        Http::fake([
            '*authentication/setup*' => Http::response([
                'token' => 'access-from-progress',
                'refreshToken' => 'refresh-from-progress',
                'expiresIn' => 86400,
            ], 200),
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
    }

    public function test_full_sync_run_records_per_step_progress(): void
    {
        $account = ChannelAccount::factory()->create([
            'provider' => 'beds24',
            'status' => 'inactive',
            'credentials' => ['invite_code' => 'INVITE-PROGRESS'],
        ]);

        $this->fullSyncFakes();

        app(SyncBeds24BookingsJob::class)->handle(
            app(Beds24SyncService::class),
            app(Beds24AlertService::class),
        );

        $log = ChannelSyncLog::query()->where('operation', 'full_sync')->firstOrFail();

        $this->assertSame('success', $log->status);
        $this->assertSame('beds24', $log->channel);
        $this->assertSame($account->id, $log->channel_account_id);
        $this->assertNotNull($log->started_at);
        $this->assertNotNull($log->completed_at);

        $steps = $log->steps;
        $this->assertCount(4, $steps);
        $this->assertSame(['catalog', 'bookings', 'bookings_push', 'calendar'], array_column($steps, 'step'));
        $this->assertSame(['completed', 'completed', 'completed', 'completed'], array_column($steps, 'status'));
        $this->assertStringContainsString('Synced 1 property, 1 room.', $steps[0]['detail']);
        $this->assertStringContainsString('1 booking imported.', $steps[1]['detail']);
    }

    public function test_sync_progress_endpoint_exposes_the_latest_run_and_accounts(): void
    {
        $account = ChannelAccount::factory()->create([
            'provider' => 'beds24',
            'status' => 'inactive',
            'credentials' => ['invite_code' => 'INVITE-PROGRESS'],
        ]);

        $this->fullSyncFakes();

        app(SyncBeds24BookingsJob::class)->handle(
            app(Beds24SyncService::class),
            app(Beds24AlertService::class),
        );

        $this->actingAs($this->superAdmin())
            ->getJson(route('admin.channels.sync.progress'))
            ->assertOk()
            ->assertJsonPath('accounts.0.id', $account->id)
            ->assertJsonPath('runs.0.account_id', $account->id)
            ->assertJsonPath('runs.0.status', 'success')
            ->assertJsonPath('runs.0.steps.0.step', 'catalog')
            ->assertJsonPath('runs.0.steps.0.status', 'completed')
            ->assertJsonPath('runs.0.steps.2.step', 'bookings_push');
    }

    public function test_failed_step_is_marked_as_failed_on_the_run_log(): void
    {
        $account = ChannelAccount::factory()->create([
            'provider' => 'beds24',
            'status' => 'active',
            'credentials' => [
                'refresh_token' => 'refresh-token',
                'access_token' => 'access-1',
                'access_token_expires_at' => now()->addHour()->toIso8601String(),
            ],
        ]);

        Http::fake([
            '*properties*' => Http::response(['data' => []], 200),
            '*bookings*' => Http::response(['success' => false, 'error' => 'Server error'], 500),
            '*inventory/rooms/calendar*' => Http::response(['data' => []], 200),
        ]);

        app(SyncBeds24BookingsJob::class)->handle(
            app(Beds24SyncService::class),
            app(Beds24AlertService::class),
        );

        $log = ChannelSyncLog::query()->where('operation', 'full_sync')->firstOrFail();

        $this->assertSame('failed', $log->status);
        $this->assertStringContainsString('bookings:', $log->error_message);
        $this->assertSame('failed', $log->steps[1]['status']);
        $this->assertStringContainsString('status code 500', $log->steps[1]['detail']);
        $this->assertSame('completed', $log->steps[0]['status']);

        $account->refresh();
        $this->assertSame('error', $account->status);
    }

    public function test_sync_progress_endpoint_requires_channels_sync_permission(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson(route('admin.channels.sync.progress'))
            ->assertForbidden();
    }

    public function test_sync_logs_a_failed_run_when_the_account_has_no_credentials(): void
    {
        $account = ChannelAccount::factory()->create([
            'provider' => 'beds24',
            'status' => 'error',
            'credentials' => [],
        ]);

        app(SyncBeds24BookingsJob::class)->handle(
            app(Beds24SyncService::class),
            app(Beds24AlertService::class),
        );

        $log = ChannelSyncLog::query()->where('operation', 'full_sync')->firstOrFail();

        $this->assertSame('failed', $log->status);
        $this->assertSame($account->id, $log->channel_account_id);
        $this->assertStringContainsString('invitation code', $log->error_message);
        $this->assertNotNull($log->completed_at);

        $this->actingAs($this->superAdmin())
            ->getJson(route('admin.channels.sync.progress'))
            ->assertOk()
            ->assertJsonPath('runs.0.status', 'failed')
            ->assertJsonPath('runs.0.error_message', $log->error_message)
            ->assertJsonPath('accounts.0.eligible', false);
    }

    public function test_sync_endpoint_warns_when_no_account_has_credentials(): void
    {
        ChannelAccount::factory()->create([
            'provider' => 'beds24',
            'status' => 'error',
            'credentials' => [],
        ]);

        $this->actingAs($this->superAdmin())
            ->from(route('admin.channels.index'))
            ->post(route('admin.channels.sync'))
            ->assertRedirect(route('admin.channels.index'))
            ->assertSessionHasErrors(['error' => 'No Beds24 account is connected yet. Enter an invitation code (or refresh token) on the Integrations page before syncing.']);
    }
}
