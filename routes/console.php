<?php

use App\Jobs\ExpireBookingHoldsJob;
use App\Jobs\GenerateRevenueSnapshotJob;
use App\Jobs\GenerateSeasonalPricingJob;
use App\Jobs\PushBeds24RatesJob;
use App\Jobs\SendCheckInNotificationJob;
use App\Jobs\SendCheckoutNotificationJob;
use App\Jobs\SendPreArrivalMessageJob;
use App\Jobs\SyncBeds24BookingsJob;
use App\Jobs\SyncBeds24MessagesJob;
use App\Models\Setting;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/**
 * Read a schedule-related setting, falling back to its default when the
 * Setting model cannot be loaded (e.g. a transient autoload hiccup during
 * composer's post-autoload-dump event). Cadence decisions are re-read on
 * every schedule:run boot, so defaults here only ever apply for the brief
 * composer window and never affect the scheduler's normal operation.
 */
$scheduleSetting = static function (string $key, mixed $default): mixed {
    try {
        return Setting::getValue($key, $default);
    } catch (\Throwable) {
        return $default;
    }
};

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(ExpireBookingHoldsJob::class)->everyFiveMinutes();
Schedule::job(SendPreArrivalMessageJob::class)->dailyAt('09:00');
Schedule::job(SendCheckInNotificationJob::class)->dailyAt('08:00');
Schedule::job(SendCheckoutNotificationJob::class)->dailyAt('08:30');
Schedule::job(GenerateRevenueSnapshotJob::class)->dailyAt('01:00');

if ($scheduleSetting('pricing_auto_generate_enabled', false)) {
    $frequency = $scheduleSetting('pricing_auto_generate_frequency', 'weekly');
    $schedule = Schedule::job(GenerateSeasonalPricingJob::class);
    match ($frequency) {
        'daily' => $schedule->dailyAt('02:00'),
        'weekly' => $schedule->weeklyOn(Schedule::MONDAY, '02:00'),
        'monthly' => $schedule->monthlyOn(1, '02:00'),
        default => $schedule->weeklyOn(Schedule::MONDAY, '02:00'),
    };
}

if ($scheduleSetting('schedule_beds24_sync_bookings_enabled', true)) {
    $frequency = $scheduleSetting('schedule_beds24_sync_bookings_frequency', 'every_five_minutes');
    $schedule = Schedule::job(SyncBeds24BookingsJob::class);
    match ($frequency) {
        'every_five_minutes' => $schedule->everyFiveMinutes(),
        'every_fifteen_minutes' => $schedule->everyFifteenMinutes(),
        'every_thirty_minutes' => $schedule->everyThirtyMinutes(),
        'hourly' => $schedule->hourly(),
        'twice_daily' => $schedule->twiceDaily(6, 18),
        'daily' => $schedule->dailyAt('06:00'),
        default => $schedule->everyFiveMinutes(),
    };
}

if ($scheduleSetting('schedule_beds24_sync_messages_enabled', true)) {
    $frequency = $scheduleSetting('schedule_beds24_sync_messages_frequency', 'every_five_minutes');
    $schedule = Schedule::job(SyncBeds24MessagesJob::class);
    match ($frequency) {
        'every_five_minutes' => $schedule->everyFiveMinutes(),
        'every_fifteen_minutes' => $schedule->everyFifteenMinutes(),
        'every_thirty_minutes' => $schedule->everyThirtyMinutes(),
        'hourly' => $schedule->hourly(),
        'twice_daily' => $schedule->twiceDaily(6, 18),
        'daily' => $schedule->dailyAt('06:00'),
        default => $schedule->everyFiveMinutes(),
    };
}

if ($scheduleSetting('schedule_beds24_push_rates_enabled', true)) {
    $frequency = $scheduleSetting('schedule_beds24_push_rates_frequency', 'hourly');
    $schedule = Schedule::job(PushBeds24RatesJob::class);
    match ($frequency) {
        'every_five_minutes' => $schedule->everyFiveMinutes(),
        'every_fifteen_minutes' => $schedule->everyFifteenMinutes(),
        'every_thirty_minutes' => $schedule->everyThirtyMinutes(),
        'hourly' => $schedule->hourly(),
        'twice_daily' => $schedule->twiceDaily(6, 18),
        'daily' => $schedule->dailyAt('06:00'),
        default => $schedule->hourly(),
    };
}
