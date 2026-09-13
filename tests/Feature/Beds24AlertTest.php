<?php

namespace Tests\Feature;

use App\Jobs\SyncBeds24BookingsJob;
use App\Mail\Beds24AlertMail;
use App\Models\ChannelAccount;
use App\Models\Setting;
use App\Services\Beds24\Beds24SyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class Beds24AlertTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.beds24.refresh_token' => null]);

        Setting::firstOrCreate(['key' => 'min_price_weekday'], ['value' => '0', 'group' => 'booking', 'label' => 'Min weekday', 'cast' => 'decimal:2']);
        Setting::firstOrCreate(['key' => 'min_price_weekend'], ['value' => '0', 'group' => 'booking', 'label' => 'Min weekend', 'cast' => 'decimal:2']);
        Setting::firstOrCreate(['key' => 'cleaning_fee'], ['value' => '0', 'group' => 'booking', 'label' => 'Cleaning', 'cast' => 'decimal:2']);
    }

    private function beds24Account(): ChannelAccount
    {
        return ChannelAccount::factory()->create([
            'provider' => 'beds24',
            'status' => 'active',
            'credentials' => [
                'refresh_token' => 'refresh-token',
                'access_token' => 'access-1',
                'access_token_expires_at' => now()->addHour()->toIso8601String(),
            ],
        ]);
    }

    private function runSyncJob(): void
    {
        app(SyncBeds24BookingsJob::class)->handle(
            app(Beds24SyncService::class),
            app(\App\Services\Beds24\Beds24AlertService::class),
        );
    }

    public function test_sync_errors_send_alert_to_the_configured_recipient(): void
    {
        $account = $this->beds24Account();
        $propertiesCalls = 0;

        Http::fake([
            '*properties*' => function () use (&$propertiesCalls) {
                $propertiesCalls++;

                return $propertiesCalls <= 2
                    ? Http::response(['success' => false, 'error' => 'Server error'], 500)
                    : Http::response(['data' => []], 200);
            },
            '*bookings*' => Http::response(['data' => []], 200),
            '*inventory/rooms/calendar*' => Http::response(['data' => []], 200),
        ]);

        Mail::fake();

        $this->runSyncJob();

        Mail::assertSent(Beds24AlertMail::class, function (Beds24AlertMail $mail) use ($account): bool {
            return $mail->account->is($account)
                && $mail->hasTo('tazarchriss@gmail.com');
        });

        $account->refresh();
        $this->assertSame('error', $account->status);
        $this->assertNotNull($account->settings['beds24_alert_sent_at'] ?? null);
        $this->assertNotSame('', $account->settings['beds24_alert_sig'] ?? '');
    }

    public function test_repeated_same_failure_is_not_resent_within_cooldown(): void
    {
        $this->beds24Account();
        $propertiesCalls = 0;

        $failingProperties = function () use (&$propertiesCalls) {
            $propertiesCalls++;

            return $propertiesCalls <= 2
                ? Http::response(['success' => false, 'error' => 'Server error'], 500)
                : Http::response(['data' => []], 200);
        };

        Http::fake([
            '*properties*' => $failingProperties,
            '*bookings*' => Http::response(['data' => []], 200),
            '*inventory/rooms/calendar*' => Http::response(['data' => []], 200),
        ]);

        Mail::fake();

        $this->runSyncJob();
        $this->runSyncJob();

        Mail::assertSent(Beds24AlertMail::class, 1);
    }

    public function test_new_error_signature_sends_alert_even_within_cooldown(): void
    {
        $this->beds24Account();
        $propertiesCalls = 0;
        $bookingsCalls = 0;

        Http::fake([
            '*properties*' => function () use (&$propertiesCalls) {
                $propertiesCalls++;

                return $propertiesCalls <= 2
                    ? Http::response(['success' => false, 'error' => 'Server error'], 500)
                    : Http::response(['data' => []], 200);
            },
            '*bookings*' => function () use (&$bookingsCalls) {
                $bookingsCalls++;

                return $bookingsCalls <= 1
                    ? Http::response(['data' => []], 200)
                    : Http::response(['success' => false, 'error' => 'Server error'], 500);
            },
            '*inventory/rooms/calendar*' => Http::response(['data' => []], 200),
        ]);

        Mail::fake();

        $this->runSyncJob();
        $this->runSyncJob();

        $sent = Mail::sent(Beds24AlertMail::class);
        $this->assertCount(2, $sent);
        $this->assertNotSame($sent[0]->errors, $sent[1]->errors);
    }

    public function test_clean_sync_sends_no_alert(): void
    {
        $this->beds24Account();

        Http::fake([
            '*properties*' => Http::response(['data' => []], 200),
            '*bookings*' => Http::response(['data' => []], 200),
            '*inventory/rooms/calendar*' => Http::response(['data' => []], 200),
        ]);

        Mail::fake();

        $this->runSyncJob();

        Mail::assertNothingSent();
    }

    public function test_sync_exception_sets_error_status_and_sends_alert(): void
    {
        $account = $this->beds24Account();

        $this->mock(Beds24SyncService::class, function ($mock): void {
            $mock->shouldReceive('synchronize')->andThrow(new \RuntimeException('boom'));
        });

        Mail::fake();

        $this->runSyncJob();

        $account->refresh();
        $this->assertSame('error', $account->status);
        $this->assertSame('boom', $account->last_error);

        Mail::assertSent(Beds24AlertMail::class, function (Beds24AlertMail $mail) use ($account): bool {
            return $mail->account->is($account) && $mail->hasTo('tazarchriss@gmail.com');
        });
    }
}