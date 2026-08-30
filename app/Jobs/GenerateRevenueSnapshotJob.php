<?php

namespace App\Jobs;

use App\Models\Property;
use App\Services\Revenue\RevenueAnalyticsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateRevenueSnapshotJob implements ShouldQueue
{
    use Queueable;

    public function handle(RevenueAnalyticsService $analytics): void
    {
        $date = now()->subDay()->startOfDay();

        $analytics->snapshotForDate($date);

        Property::query()
            ->where('status', 'active')
            ->each(fn (Property $property) => $analytics->snapshotForDate($date, $property));
    }
}
