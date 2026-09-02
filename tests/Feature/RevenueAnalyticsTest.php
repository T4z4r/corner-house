<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\Reservation;
use App\Services\Revenue\RevenueAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RevenueAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_snapshot_records_cancellations_for_the_date(): void
    {
        $property = Property::factory()->create();

        $today = Carbon::today();

        Reservation::factory()->create([
            'property_id' => $property->id,
            'status' => 'cancelled',
            'cancelled_at' => $today,
            'check_in' => $today->copy()->addDays(2),
            'check_out' => $today->copy()->addDays(4),
        ]);

        $snapshot = app(RevenueAnalyticsService::class)
            ->snapshotForDate($today, $property);

        $this->assertSame(1, $snapshot->cancellations_count);
        $this->assertSame(0, $snapshot->bookings_count);
    }

    public function test_snapshot_counts_only_bookings_on_the_snapshot_date(): void
    {
        $property = Property::factory()->create();
        $today = Carbon::today();

        Reservation::factory()->create([
            'property_id' => $property->id,
            'status' => 'confirmed',
            'check_in' => $today,
            'check_out' => $today->copy()->addDays(2),
            'source' => 'direct',
        ]);

        $snapshot = app(RevenueAnalyticsService::class)
            ->snapshotForDate($today, $property);

        $this->assertSame(1, $snapshot->bookings_count);
        $this->assertSame(1, $snapshot->direct_bookings);
    }
}
