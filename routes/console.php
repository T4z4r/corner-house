<?php

use App\Jobs\ExpireBookingHoldsJob;
use App\Jobs\GenerateRevenueSnapshotJob;
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

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new ExpireBookingHoldsJob)->everyFiveMinutes();
Schedule::job(new SendPreArrivalMessageJob)->dailyAt('09:00');
Schedule::job(new SendCheckInNotificationJob)->dailyAt('08:00');
Schedule::job(new SendCheckoutNotificationJob)->dailyAt('08:30');
Schedule::job(new GenerateRevenueSnapshotJob)->dailyAt('01:00');

if (Setting::getValue('schedule_beds24_sync_bookings_enabled', true)) {
    $frequency = Setting::getValue('schedule_beds24_sync_bookings_frequency', 'every_five_minutes');
    $schedule = Schedule::job(new SyncBeds24BookingsJob);
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

if (Setting::getValue('schedule_beds24_sync_messages_enabled', true)) {
    $frequency = Setting::getValue('schedule_beds24_sync_messages_frequency', 'every_five_minutes');
    $schedule = Schedule::job(new SyncBeds24MessagesJob);
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

if (Setting::getValue('schedule_beds24_push_rates_enabled', true)) {
    $frequency = Setting::getValue('schedule_beds24_push_rates_frequency', 'hourly');
    $schedule = Schedule::job(new PushBeds24RatesJob);
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
