@extends('layouts.admin.app')

@section('title', 'Dashboard')

@section('content')
    <div class="ch-page-header">
        <div>
            <div class="ch-breadcrumb">Admin</div>
            <h4>Dashboard</h4>
            <p class="ch-subtitle">{{ now()->format('l, d F Y') }}</p>
        </div>
    </div>

    <div class="row g-3 mb-4">
        @php
            $cards = [
                ['label' => 'Today\'s Arrivals', 'value' => $stats['arrivals'], 'icon' => 'bi-box-arrow-in-down-right', 'color' => 'bg-primary-subtle text-primary'],
                ['label' => 'Today\'s Departures', 'value' => $stats['departures'], 'icon' => 'bi-box-arrow-right', 'color' => 'bg-warning-subtle text-warning'],
                ['label' => 'Current Occupancy', 'value' => $stats['occupancy'].'%', 'icon' => 'bi-graph-up-arrow', 'color' => 'bg-success-subtle text-success'],
                ['label' => 'Revenue', 'value' => '£'.number_format($stats['revenue'], 2), 'icon' => 'bi-cash-stack', 'color' => 'bg-info-subtle text-info'],
                ['label' => 'ADR', 'value' => '£'.number_format($stats['adr'], 2), 'icon' => 'bi-currency-pound', 'color' => 'bg-secondary-subtle text-secondary'],
                ['label' => 'RevPAR', 'value' => '£'.number_format($stats['revpar'], 2), 'icon' => 'bi-bar-chart', 'color' => 'bg-dark-subtle text-dark'],
                ['label' => 'Upcoming Bookings', 'value' => $stats['upcoming_bookings'], 'icon' => 'bi-journal-bookmark', 'color' => 'bg-primary-subtle text-primary'],
                ['label' => 'Pending Payments', 'value' => $stats['pending_payments'], 'icon' => 'bi-hourglass-split', 'color' => 'bg-danger-subtle text-danger'],
            ];
        @endphp

        @foreach ($cards as $card)
            <div class="col-6 col-md-3">
                <div class="ch-stat-card">
                    <div class="ch-stat-icon {{ $card['color'] }}">
                        <i class="bi {{ $card['icon'] }}"></i>
                    </div>
                    <div>
                        <div class="ch-stat-value">{{ $card['value'] }}</div>
                        <div class="ch-stat-label">{{ $card['label'] }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white"><h6 class="mb-0">Monthly Revenue</h6></div>
                <div class="card-body" style="min-height: 280px;">
                    <canvas id="dashboardRevenueChart" height="120"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white"><h6 class="mb-0">Booking Sources</h6></div>
                <div class="card-body" style="min-height: 280px;">
                    <canvas id="dashboardSourceChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white"><h6 class="mb-0">Recent Activity</h6></div>
                <div class="card-body p-0">
                    @if ($recentAuditLogs->isEmpty())
                        <div class="ch-empty">
                            <i class="bi bi-activity"></i>
                            <div class="lead">No activity recorded yet</div>
                            <div class="small">Actions across the platform will appear here.</div>
                        </div>
                    @else
                        <ul class="list-group list-group-flush">
                            @foreach ($recentAuditLogs as $log)
                                <li class="list-group-item">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-person-circle me-2 text-secondary"></i>
                                        <div class="flex-grow-1">
                                            <div class="small">{{ $log->user?->name ?? 'System' }} <strong>{{ $log->action }}</strong></div>
                                            <div class="text-muted small">{{ $log->module }} · {{ $log->created_at->diffForHumans() }}</div>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Channel Synchronization</h6>
                    @if ($channelAccount?->status === 'active')
                        <span class="ch-badge ch-badge-success"><span class="dot"></span>Connected</span>
                    @else
                        <span class="ch-badge ch-badge-warning"><span class="dot"></span>Not configured</span>
                    @endif
                </div>
                <div class="card-body">
                    @if ($lastSync)
                        <p class="mb-1">Last successful sync: {{ $lastSync->completed_at?->diffForHumans() }}</p>
                        <p class="small text-muted mb-0">{{ $lastSync->operation }}</p>
                    @else
                        <div class="ch-empty border-0 p-0">
                            <i class="bi bi-plug"></i>
                            <div class="lead">No channels connected</div>
                            <div class="small">Connect Beds24 in the Channels module to sync bookings.</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const series = @json($series);
    const sources = @json($sources);
    if (window.Chart) {
        new Chart(document.getElementById('dashboardRevenueChart'), {
            type: 'line',
            data: { labels: series.labels, datasets: [{ label: 'Revenue', data: series.revenue, borderColor: '#1f6f43' }] }
        });
        new Chart(document.getElementById('dashboardSourceChart'), {
            type: 'doughnut',
            data: { labels: Object.keys(sources), datasets: [{ data: Object.values(sources), backgroundColor: ['#1f6f43', '#c9a227', '#6b7280', '#174f30'] }] }
        });
    }
});
</script>
@endpush
