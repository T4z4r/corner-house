<?php

namespace Tests\Feature;

use App\Jobs\ExpireBookingHoldsJob;
use App\Jobs\GenerateRevenueSnapshotJob;
use App\Jobs\GenerateSeasonalPricingJob;
use App\Jobs\PushBeds24RatesJob;
use App\Jobs\SendPreArrivalMessageJob;
use App\Jobs\SyncBeds24BookingsJob;
use App\Models\BookingHold;
use App\Models\ChannelAccount;
use App\Models\ChannelMapping;
use App\Models\Communication;
use App\Models\Guest;
use App\Models\PricingRule;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\Setting;
use App\Services\Beds24\Beds24MessageService;
use App\Services\Beds24\Beds24SyncService;
use App\Services\Booking\BookingHoldService;
use App\Services\Channel\ChannelManager;
use App\Services\Channel\ChannelProviderInterface;
use App\Services\Notification\NotificationService;
use App\Services\Pricing\PricingEngine;
use App\Services\Pricing\SeasonalPricingAutomationService;
use App\Services\Revenue\RevenueAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class CronJobLoggingTest extends TestCase
{
    use RefreshDatabase;

    private function beds24Account(): ChannelAccount
    {
        return ChannelAccount::factory()->create([
            'provider' => 'beds24',
            'status' => 'active',
            'credentials' => [
                'access_token' => 'access-1',
                'access_token_expires_at' => now()->addHour()->toIso8601String(),
            ],
        ]);
    }

    public function test_beds24_sync_job_logs_fetched_and_pushed_counts_for_each_account(): void
    {
        $this->beds24Account();

        Http::fake([
            '*properties*' => Http::response([
                'data' => [[
                    'id' => 2001,
                    'name' => 'Corner House B24',
                    'city' => 'Towcester',
                    'postcode' => 'NN12 1AA',
                    'country' => 'GB',
                    'roomTypes' => [[
                        'id' => 77,
                        'name' => 'Oak Suite',
                        'maxPeople' => 3,
                        'minStay' => 2,
                    ]],
                ]],
            ], 200),
            '*bookings*' => Http::response(['data' => []], 200),
            '*inventory/rooms/calendar*' => Http::response(['data' => []], 200),
        ]);

        Log::spy();

        app(SyncBeds24BookingsJob::class)->handle(app(Beds24SyncService::class));

        Log::shouldHaveReceived('info')
            ->withArgs(fn (string $message, array $context): bool => $message === 'Beds24 sync complete'
                && ($context['account_id'] ?? null) === 1
                && ($context['provider'] ?? null) === 'beds24'
                && ($context['properties'] ?? null) === 1
                && ($context['rooms'] ?? null) === 1
                && ($context['bookings'] ?? null) === 0
                && ($context['bookings_pushed'] ?? null) === 0
                && ($context['errors'] ?? null) === []);
    }

    public function test_beds24_message_sync_logs_each_fetched_message(): void
    {
        $this->beds24Account();

        Http::fake([
            '*bookings/messages*' => Http::response([
                'data' => [[
                    'id' => 550,
                    'bookingId' => 1001,
                    'time' => '2026-09-01T10:00:00Z',
                    'read' => false,
                    'message' => 'Can we check in early?',
                    'source' => 'guest',
                ]],
            ], 200),
        ]);

        Log::spy();

        app(Beds24MessageService::class)->syncAll();

        Log::shouldHaveReceived('info')
            ->withArgs(fn (string $message, array $context): bool => $message === 'Beds24 messages fetched and stored'
                && ($context['account_id'] ?? null) === 1
                && ($context['total'] ?? null) === 1
                && ($context['created'] ?? null) === 1
                && ($context['message_ids'] ?? null) === [550]);
    }

    public function test_push_rates_job_logs_the_submitted_payload(): void
    {
        $account = $this->beds24Account();
        $property = Property::factory()->create();
        $room = Room::factory()->create([
            'property_id' => $property->id,
            'status' => 'active',
        ]);
        $mapping = ChannelMapping::create([
            'channel_account_id' => $account->id,
            'provider' => 'beds24',
            'property_id' => $property->id,
            'room_id' => $room->id,
            'external_property_id' => '2001',
            'external_room_id' => '77',
            'status' => 'active',
        ]);

        $provider = Mockery::mock(ChannelProviderInterface::class);
        $provider->shouldReceive('pushRates')->once()->andReturn(true);

        $channels = Mockery::mock(ChannelManager::class);
        $channels->shouldReceive('provider')->once()->with('beds24')->andReturn($provider);

        $pricing = Mockery::mock(PricingEngine::class);
        $pricing->shouldReceive('calculateForRange')
            ->once()
            ->andReturn(['per_night' => [now()->toDateString() => 120.0], 'minimum_stay' => 2]);
        $pricing->shouldReceive('lengthOfStayDiscountForRoom')->once()->andReturn(['pct' => 0]);

        Log::spy();

        (new PushBeds24RatesJob)->handle($channels, $pricing);

        Log::shouldHaveReceived('info')
            ->withArgs(fn (string $message, array $context): bool => $message === 'Channel rates pushed to provider'
                && ($context['mapping_id'] ?? null) === $mapping->id
                && ($context['account_id'] ?? null) === $account->id
                && ($context['external_room_id'] ?? null) === '77'
                && ($context['from'] ?? null) === now()->toDateString()
                && ($context['price'] ?? null) === 120.0
                && ($context['minimum_stay'] ?? null) === 2
                && ($context['pushed'] ?? null) === true);
    }

    public function test_revenue_snapshot_job_logs_generated_snapshot_data(): void
    {
        Property::factory()->create(['status' => 'active']);

        Log::spy();

        (new GenerateRevenueSnapshotJob)->handle(app(RevenueAnalyticsService::class));

        Log::shouldHaveReceived('info')
            ->withArgs(fn (string $message, array $context): bool => $message === 'Revenue snapshots generated'
                && ($context['date'] ?? null) === now()->subDay()->toDateString()
                && ($context['property_count'] ?? null) === 1
                && isset($context['snapshots'])
                && count($context['snapshots']) === 2);
    }

    public function test_expired_holds_job_logs_released_count(): void
    {
        BookingHold::factory()->create([
            'status' => 'active',
            'expires_at' => now()->subMinute(),
        ]);

        Log::spy();

        (new ExpireBookingHoldsJob)->handle(app(BookingHoldService::class));

        Log::shouldHaveReceived('info')
            ->withArgs(fn (string $message, array $context): bool => $message === 'Booking holds expired'
                && ($context['released'] ?? null) === 1);
    }

    public function test_pre_arrival_job_logs_each_delivered_message(): void
    {
        $guest = Guest::factory()->create();
        $room = Room::factory()->create();
        $reservation = Reservation::factory()->create([
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'status' => 'confirmed',
            'check_in' => now()->addDay()->toDateString(),
        ]);
        $communication = Communication::factory()->create([
            'guest_id' => $guest->id,
            'reservation_id' => $reservation->id,
            'recipient' => $guest->email,
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $notifications = Mockery::mock(NotificationService::class);
        $notifications->shouldReceive('sendForEvent')
            ->once()
            ->with('pre_arrival', Mockery::type(Reservation::class))
            ->andReturn($communication);

        Log::spy();

        (new SendPreArrivalMessageJob)->handle($notifications);

        Log::shouldHaveReceived('info')
            ->withArgs(fn (string $message, array $context): bool => $message === 'Pre-arrival guest message delivered'
                && ($context['reservation_id'] ?? null) === $reservation->id
                && ($context['reference'] ?? null) === $reservation->reference
                && ($context['communication_id'] ?? null) === $communication->id
                && ($context['recipient'] ?? null) === $guest->email
                && ($context['status'] ?? null) === 'sent');
    }

    public function test_seasonal_pricing_job_logs_generated_rule_details(): void
    {
        Setting::query()->create([
            'group' => 'pricing',
            'key' => 'pricing_auto_generate_enabled',
            'value' => '1',
            'type' => 'boolean',
            'label' => 'Auto generate seasonal pricing',
            'cast' => 'boolean',
        ]);

        $property = Property::factory()->create(['status' => 'active']);
        $rule = PricingRule::create([
            'property_id' => $property->id,
            'room_id' => null,
            'name' => 'Summer uplift',
            'rule_type' => 'seasonal',
            'start_date' => now()->addMonth()->toDateString(),
            'end_date' => now()->addMonths(2)->toDateString(),
            'priority' => 5,
            'adjustment_type' => 'percent',
            'adjustment_value' => 10,
            'is_enabled' => true,
            'generated_by_ai' => true,
        ]);

        $automation = Mockery::mock(SeasonalPricingAutomationService::class);
        $automation->shouldReceive('generateForProperty')
            ->once()
            ->andReturn([
                'summary' => 'Generated summer plan.',
                'created' => 1,
                'updated' => 0,
                'rules' => collect([$rule]),
            ]);

        Log::spy();

        (new GenerateSeasonalPricingJob)->handle($automation);

        Log::shouldHaveReceived('info')
            ->withArgs(fn (string $message, array $context): bool => $message === 'Seasonal pricing auto-generated.'
                && ($context['property_id'] ?? null) === $property->id
                && ($context['summary'] ?? null) === 'Generated summer plan.'
                && ($context['created'] ?? null) === 1
                && ($context['rules'] ?? null) === [[
                    'id' => $rule->id,
                    'name' => 'Summer uplift',
                    'rule_type' => 'seasonal',
                    'start_date' => now()->addMonth()->toDateString(),
                    'end_date' => now()->addMonths(2)->toDateString(),
                    'adjustment_type' => 'percent',
                    'adjustment_value' => 10.0,
                    'priority' => 5,
                ]]);
    }
}
