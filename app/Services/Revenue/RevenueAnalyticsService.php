<?php

namespace App\Services\Revenue;

use App\Models\Property;
use App\Models\Reservation;
use App\Models\RevenueSnapshot;
use App\Models\Room;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class RevenueAnalyticsService
{
    /**
     * @return array{
     *     arrivals: int,
     *     departures: int,
     *     occupancy: float,
     *     upcoming_bookings: int,
     *     revenue: float,
     *     adr: float,
     *     revpar: float,
     *     pending_payments: int,
     *     cancellation_rate: float,
     *     cancellations: int,
     *     average_booking_value: float,
     *     direct_bookings: int,
     *     ota_bookings: int
     * }
     */
    public function dashboardStats(?int $propertyId = null, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $from ??= now()->startOfMonth();
        $to ??= now()->endOfMonth();
        $today = now()->toDateString();

        $base = Reservation::query()
            ->when($propertyId, fn ($q) => $q->where('property_id', $propertyId));

        $active = (clone $base)->active();

        $arrivals = (clone $active)->whereDate('check_in', $today)->count();
        $departures = (clone $active)->whereDate('check_out', $today)->count();
        $upcoming = (clone $active)->whereDate('check_in', '>', $today)->count();

        $period = (clone $base)
            ->where('status', '!=', 'cancelled')
            ->whereDate('check_in', '<=', $to->toDateString())
            ->whereDate('check_out', '>=', $from->toDateString());

        $revenue = (float) (clone $period)->whereIn('status', ['confirmed', 'checked_in', 'checked_out'])->sum('total_amount');
        $bookings = (clone $period)->whereIn('status', ['confirmed', 'checked_in', 'checked_out'])->count();
        $nightsSold = (clone $period)->whereIn('status', ['confirmed', 'checked_in', 'checked_out'])->get()
            ->sum(fn (Reservation $reservation): int => max(1, $reservation->check_in->diffInDays($reservation->check_out)));

        $roomCount = Room::query()
            ->when($propertyId, fn ($q) => $q->where('property_id', $propertyId))
            ->where('status', 'active')
            ->count();

        $periodNights = max(1, $from->diffInDays($to) + 1);
        $availableNights = max(1, $roomCount * $periodNights);
        $occupancy = round(($nightsSold / $availableNights) * 100, 1);
        $adr = $nightsSold > 0 ? round($revenue / $nightsSold, 2) : 0.0;
        $revpar = round($adr * ($occupancy / 100), 2);

        $cancelled = (clone $base)->where('status', 'cancelled')
            ->whereDate('cancelled_at', '>=', $from)->whereDate('cancelled_at', '<=', $to)->count();
        $created = (clone $base)->whereDate('created_at', '>=', $from)->whereDate('created_at', '<=', $to)->count();

        return [
            'arrivals' => $arrivals,
            'departures' => $departures,
            'occupancy' => $occupancy,
            'upcoming_bookings' => $upcoming,
            'revenue' => round($revenue, 2),
            'adr' => $adr,
            'revpar' => $revpar,
            'pending_payments' => (clone $base)->where('payment_status', 'unpaid')->whereNotIn('status', ['cancelled', 'no_show'])->count(),
            'cancellation_rate' => $created > 0 ? round(($cancelled / $created) * 100, 1) : 0.0,
            'cancellations' => $cancelled,
            'average_booking_value' => $bookings > 0 ? round($revenue / $bookings, 2) : 0.0,
            'direct_bookings' => (clone $period)->whereIn('source', ['direct', 'manual'])->count(),
            'ota_bookings' => (clone $period)->whereNotIn('source', ['direct', 'manual'])->count(),
        ];
    }

    /**
     * @return array{labels: array<int, string>, revenue: array<int, float>, occupancy: array<int, float>}
     */
    public function monthlySeries(?int $propertyId = null, int $months = 6): array
    {
        $labels = [];
        $revenue = [];
        $occupancy = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $start = now()->subMonths($i)->startOfMonth();
            $end = $start->copy()->endOfMonth();
            $stats = $this->dashboardStats($propertyId, $start, $end);
            $labels[] = $start->format('M Y');
            $revenue[] = $stats['revenue'];
            $occupancy[] = $stats['occupancy'];
        }

        return compact('labels', 'revenue', 'occupancy');
    }

    /**
     * @return Collection<string, int>
     */
    public function bookingsBySource(?int $propertyId = null): Collection
    {
        return Reservation::query()
            ->when($propertyId, fn ($q) => $q->where('property_id', $propertyId))
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->selectRaw('source, count(*) as aggregate')
            ->groupBy('source')
            ->pluck('aggregate', 'source');
    }

    public function snapshotForDate(Carbon $date, ?Property $property = null): RevenueSnapshot
    {
        $stats = $this->dashboardStats($property?->id, $date->copy()->startOfDay(), $date->copy()->endOfDay());

        return RevenueSnapshot::updateOrCreate(
            [
                'property_id' => $property?->id,
                'snapshot_date' => $date->toDateString(),
            ],
            [
                'revenue' => $stats['revenue'],
                'occupancy_pct' => $stats['occupancy'],
                'adr' => $stats['adr'],
                'revpar' => $stats['revpar'],
                'bookings_count' => $stats['direct_bookings'] + $stats['ota_bookings'],
                'cancellations_count' => $stats['cancellations'],
                'direct_bookings' => $stats['direct_bookings'],
                'ota_bookings' => $stats['ota_bookings'],
            ],
        );
    }
}
