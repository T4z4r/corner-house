<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_schedule_registers_default_cadences_when_settings_are_unavailable(): void
    {
        $events = app(Schedule::class)->events();

        $this->assertContainsScheduled(events: $events, expression: '*/5 * * * *', job: 'SyncBeds24BookingsJob');
        $this->assertContainsScheduled(events: $events, expression: '*/5 * * * *', job: 'SyncBeds24MessagesJob');
        $this->assertContainsScheduled(events: $events, expression: '0 * * * *', job: 'PushBeds24RatesJob');
        $this->assertContainsScheduled(events: $events, expression: '*/5 * * * *', job: 'ExpireBookingHoldsJob');
        $this->assertNotContainsScheduled(events: $events, job: 'GenerateSeasonalPricingJob');
    }

    private function assertContainsScheduled(array $events, string $expression, string $job): void
    {
        $matches = collect($events)->filter(
            fn (Event $event): bool => $event->expression === $expression && str_contains((string) $event->getSummaryForDisplay(), $job)
        );

        $this->assertTrue($matches->isNotEmpty(), sprintf('Expected scheduled job [%s] at [%s] to be registered.', $job, $expression));
    }

    private function assertNotContainsScheduled(array $events, string $job): void
    {
        $matches = collect($events)->filter(
            fn (Event $event): bool => str_contains((string) $event->getSummaryForDisplay(), $job)
        );

        $this->assertTrue($matches->isEmpty(), sprintf('Expected scheduled job [%s] not to be registered.', $job));
    }
}
