<?php

namespace Tests\Feature;

use App\Jobs\SyncBeds24BookingsJob;
use App\Models\ChannelAccount;
use App\Models\Setting;
use App\Services\Beds24\Beds24AlertService;
use App\Services\Beds24\Beds24AuthService;
use App\Services\Beds24\Beds24SyncService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class Beds24InviteCodeAuthTest extends TestCase
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

    private function accountWith(array $credentials): ChannelAccount
    {
        return ChannelAccount::factory()->create([
            'provider' => 'beds24',
            'status' => 'inactive',
            'credentials' => $credentials,
        ]);
    }

    public function test_access_token_is_minted_from_an_invite_code_when_no_refresh_token_exists(): void
    {
        $account = $this->accountWith(['invite_code' => 'INVITE-ABC']);

        Http::fake([
            '*authentication/setup*' => Http::response([
                'token' => 'access-from-invite',
                'refreshToken' => 'refresh-from-invite',
                'expiresIn' => 86400,
            ], 200),
        ]);

        $token = app(Beds24AuthService::class)->accessToken($account);

        $this->assertSame('access-from-invite', $token);

        $account->refresh();
        $this->assertSame('refresh-from-invite', $account->credentials['refresh_token']);
        $this->assertSame('access-from-invite', $account->credentials['access_token']);
        $this->assertSame('INVITE-ABC', $account->credentials['invite_code']);
        $this->assertSame('active', $account->status);
        $this->assertNull($account->last_error);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'authentication/setup')
            && in_array('INVITE-ABC', $request->header('code'), true));
    }

    public function test_access_token_falls_back_to_the_invite_code_when_the_refresh_token_is_rejected(): void
    {
        $account = $this->accountWith([
            'refresh_token' => 'stale-refresh',
            'invite_code' => 'INVITE-DEF',
        ]);

        Http::fake([
            '*authentication/token*' => Http::response(['success' => false, 'error' => 'Token not valid'], 401),
            '*authentication/setup*' => Http::response([
                'token' => 'access-recovered',
                'refreshToken' => 'refresh-recovered',
                'expiresIn' => 86400,
            ], 200),
        ]);

        $token = app(Beds24AuthService::class)->accessToken($account);

        $this->assertSame('access-recovered', $token);

        $account->refresh();
        $this->assertSame('refresh-recovered', $account->credentials['refresh_token']);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'authentication/setup'));
    }

    public function test_access_token_recovers_from_a_token_endpoint_timeout_with_the_invite_code(): void
    {
        $account = $this->accountWith([
            'refresh_token' => 'stale-refresh',
            'invite_code' => 'INVITE-TIMEOUT',
        ]);

        Http::fake([
            '*authentication/token*' => Http::failedConnection(),
            '*authentication/setup*' => Http::response([
                'token' => 'access-recovered-after-timeout',
                'refreshToken' => 'refresh-recovered-after-timeout',
                'expiresIn' => 86400,
            ], 200),
        ]);

        $token = app(Beds24AuthService::class)->accessToken($account);

        $this->assertSame('access-recovered-after-timeout', $token);
        $this->assertGreaterThanOrEqual(2, collect(Http::recorded())->filter(
            fn ($request) => str_contains($request->url(), 'authentication/token')
        )->count());
        Http::assertSent(fn ($request) => str_contains($request->url(), 'authentication/setup'));
    }

    public function test_access_token_raises_when_no_credentials_or_invite_code_exist(): void
    {
        $account = $this->accountWith([]);

        Http::fake();

        $this->expectException(\RuntimeException::class);
        app(Beds24AuthService::class)->accessToken($account);

        Http::assertNothingSent();
    }

    public function test_access_token_falls_back_to_the_system_refresh_token_when_the_account_token_is_rejected(): void
    {
        config(['services.beds24.refresh_token' => 'system-refresh-valid']);

        $account = $this->accountWith([
            'refresh_token' => 'stale-account-refresh',
        ]);

        Http::fake([
            '*authentication/token*' => Http::sequence([
                Http::response(['error' => 'Token not valid'], 401),
                Http::response([
                    'token' => 'access-from-system',
                    'expiresIn' => 86400,
                ], 200),
            ]),
        ]);

        $token = app(Beds24AuthService::class)->accessToken($account);

        $this->assertSame('access-from-system', $token);

        $account->refresh();
        $this->assertSame('system-refresh-valid', $account->credentials['refresh_token']);
        $this->assertSame('access-from-system', $account->credentials['access_token']);
    }

    public function test_access_token_does_not_retry_a_system_token_identical_to_the_dead_account_token(): void
    {
        config(['services.beds24.refresh_token' => 'dead-system-refresh']);

        $account = $this->accountWith([
            'refresh_token' => 'dead-system-refresh',
        ]);

        Http::fake([
            '*authentication/token*' => Http::response(['error' => 'Token not valid'], 401),
        ]);

        $this->expectException(\RuntimeException::class);
        app(Beds24AuthService::class)->accessToken($account);

        Http::assertSentCount(1);
    }

    public function test_a_sync_powered_only_by_an_invite_code_imports_bookings(): void
    {
        $account = $this->accountWith(['invite_code' => 'INVITE-SYNC']);

        $checkIn = now()->addDays(20)->toDateString();
        $checkOut = now()->addDays(23)->toDateString();

        Http::fake([
            '*authentication/setup*' => Http::response([
                'token' => 'access-from-cron',
                'refreshToken' => 'refresh-from-cron',
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
        $this->assertSame('refresh-from-cron', $account->credentials['refresh_token']);
        $this->assertSame('ok', $account->settings['last_sync_health']);
    }
}
