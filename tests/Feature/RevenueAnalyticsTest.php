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

    public function test_dashboard_revenue_excludes_pending_and_hold_bookings(): void
    {
        $property = Property::factory()->create();
        $from = now()->startOfMonth();
        $to = now()->endOfMonth();

        Reservation::factory()->create([
            'property_id' => $property->id,
            'status' => 'confirmed',
            'check_in' => $from->copy()->addDays(2),
            'check_out' => $from->copy()->addDays(4),
            'total_amount' => 100.00,
        ]);
        Reservation::factory()->create([
            'property_id' => $property->id,
            'status' => 'pending',
            'check_in' => $from->copy()->addDays(5),
            'check_out' => $from->copy()->addDays(7),
            'total_amount' => 200.00,
        ]);
        Reservation::factory()->create([
            'property_id' => $property->id,
            'status' => 'hold',
            'check_in' => $from->copy()->addDays(8),
            'check_out' => $from->copy()->addDays(10),
            'total_amount' => 300.00,
        ]);

        $stats = app(RevenueAnalyticsService::class)->dashboardStats($property->id, $from, $to);

        $this->assertSame(100.00, $stats['revenue']);
    }
}
