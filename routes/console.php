<?php

use App\Jobs\ExpireBookingHoldsJob;
use App\Jobs\GenerateRevenueSnapshotJob;
use App\Jobs\PushBeds24RatesJob;
use App\Jobs\SendCheckInNotificationJob;
use App\Jobs\SendCheckoutNotificationJob;
use App\Jobs\SendPreArrivalMessageJob;
use App\Jobs\SyncBeds24BookingsJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new ExpireBookingHoldsJob)->everyFiveMinutes();
Schedule::job(new SyncBeds24BookingsJob)->everyFiveMinutes();
Schedule::job(new PushBeds24RatesJob)->hourly();
Schedule::job(new SendPreArrivalMessageJob)->dailyAt('09:00');
Schedule::job(new SendCheckInNotificationJob)->dailyAt('08:00');
Schedule::job(new SendCheckoutNotificationJob)->dailyAt('08:30');
Schedule::job(new GenerateRevenueSnapshotJob)->dailyAt('01:00');
