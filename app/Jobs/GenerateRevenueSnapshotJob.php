<?php

namespace App\Jobs;

use App\Jobs\Concerns\TracksCronRun;
use App\Models\Property;
use App\Models\RevenueSnapshot;
use App\Services\Revenue\RevenueAnalyticsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GenerateRevenueSnapshotJob implements ShouldQueue
{
    use Queueable, TracksCronRun;

    public function handle(RevenueAnalyticsService $analytics): void
    {
        $this->trackCronRun(function () use ($analytics): void {
            $date = now()->subDay()->startOfDay();

            $snapshots = [];
            $snapshots[] = $analytics->snapshotForDate($date);

            Property::query()
                ->where('status', 'active')
                ->each(function (Property $property) use ($analytics, $date, &$snapshots): void {
                    $snapshots[] = $analytics->snapshotForDate($date, $property);
                });

            Log::info('Revenue snapshots generated', [
                'date' => $date->toDateString(),
                'property_count' => count($snapshots) - 1,
                'snapshots' => collect($snapshots)->map(static fn (RevenueSnapshot $snapshot): array => [
                    'property_id' => $snapshot->property_id,
                    'revenue' => (float) $snapshot->revenue,
                    'occupancy_pct' => (float) $snapshot->occupancy_pct,
                    'adr' => (float) $snapshot->adr,
                    'revpar' => (float) $snapshot->revpar,
                    'bookings_count' => (int) $snapshot->bookings_count,
                    'cancellations_count' => (int) $snapshot->cancellations_count,
                ])->values()->all(),
            ]);
        });
    }
}