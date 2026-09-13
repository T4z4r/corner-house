@extends('layouts.admin.app')

@section('title', 'Cron Jobs')

@section('content')
    @php
        $formatDuration = static function (?int $ms): string {
            if ($ms === null) {
                return '—';
            }

            return $ms >= 1000 ? number_format($ms / 1000, 1).'s' : $ms.'ms';
        };

        $frequencies = [
            '*/5 * * * *' => 'Every 5 minutes',
            '*/15 * * * *' => 'Every 15 minutes',
            '*/30 * * * *' => 'Every 30 minutes',
            '0 * * * *' => 'Hourly',
            '0 6,18 * * *' => 'Twice daily (06:00 & 18:00)',
            '0 1 * * *' => 'Daily at 01:00',
            '0 2 * * *' => 'Daily at 02:00',
            '0 6 * * *' => 'Daily at 06:00',
            '0 8 * * *' => 'Daily at 08:00',
            '0 9 * * *' => 'Daily at 09:00',
            '0 0 1 * *' => 'Monthly on the 1st',
            '0 2 1 * *' => 'Monthly on the 1st',
            '0 8 * * 1' => 'Weekly Mon 08:00',
            '0 2 * * 1' => 'Weekly Mon 02:00',
        ];

        $scheduleLabel = static function (string $name) use ($frequencies, $cadence): string {
            $expression = $cadence[$name] ?? null;

            if ($expression === null) {
                return 'Not scheduled';
            }

            return $frequencies[$expression] ?? $expression;
        };
    @endphp

    <div class="ch-page-header">
        <div>
            <div class="ch-breadcrumb">System / Cron Jobs</div>
            <h4>Cron Jobs</h4>
            <p class="ch-subtitle">Scheduled jobs and their recent run history</p>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Runs (last 24h)</div>
                    <div class="fs-4 fw-bold">{{ $summary['last_24h'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Successful</div>
                    <div class="fs-4 fw-bold text-success">{{ $summary['success'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Failed</div>
                    <div class="fs-4 fw-bold text-danger">{{ $summary['failed'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Running now</div>
                    <div class="fs-4 fw-bold text-warning">{{ $summary['running'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h6 class="mb-3">Scheduled Jobs</h6>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Job</th>
                            <th>Schedule</th>
                            <th>Last run</th>
                            <th>Status</th>
                            <th>Duration</th>
                            <th class="text-end">Successful</th>
                            <th class="text-end">Failed</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($jobs as $job)
                            <tr>
                                <td class="small">{{ $job['label'] }}</td>
                                <td class="small">{{ $scheduleLabel($job['name']) }}</td>
                                <td class="small">{{ $job['last_run']?->started_at?->format('d M Y H:i') ?? '—' }}</td>
                                <td>
                                    @switch($job['last_run']?->status)
                                        @case('success')
                                            <span class="ch-badge ch-badge-success">Success</span>
                                            @break
                                        @case('failed')
                                            <span class="ch-badge ch-badge-danger">Failed</span>
                                            @break
                                        @case('running')
                                            <span class="ch-badge ch-badge-warning">Running</span>
                                            @break
                                        @default
                                            <span class="ch-badge ch-badge-muted">Never</span>
                                    @endswitch
                                </td>
                                <td class="small">{{ $formatDuration($job['last_run']?->duration_ms) }}</td>
                                <td class="text-end text-success fw-bold">{{ $job['success_count'] }}</td>
                                <td class="text-end text-danger fw-bold">{{ $job['failure_count'] }}</td>
                            </tr>
                        @empty
                            @include('layouts.admin._empty', [
                                'icon' => 'bi-clock-history',
                                'message' => 'No scheduled jobs registered',
                                'hint' => 'Scheduled jobs will appear here once they have been run at least once.',
                                'colspan' => 7,
                            ])
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="ch-toolbar mb-3">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-3 col-6">
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="running" @selected(request('status') === 'running')>Running</option>
                    <option value="success" @selected(request('status') === 'success')>Successful</option>
                    <option value="failed" @selected(request('status') === 'failed')>Failed</option>
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-ch-primary"><i class="bi bi-funnel me-1"></i>Apply filters</button>
                @if (request('status'))
                    <a href="{{ route('admin.cron-jobs') }}" class="btn btn-light">Clear</a>
                @endif
            </div>
        </form>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Job</th>
                            <th>Status</th>
                            <th>Started</th>
                            <th>Duration</th>
                            <th>Error</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($runs as $run)
                            <tr>
                                <td class="small">{{ $run->job }}</td>
                                <td>
                                    @switch($run->status)
                                        @case('success')
                                            <span class="ch-badge ch-badge-success">Success</span>
                                            @break
                                        @case('failed')
                                            <span class="ch-badge ch-badge-danger">Failed</span>
                                            @break
                                        @default
                                            <span class="ch-badge ch-badge-warning">Running</span>
                                    @endswitch
                                </td>
                                <td class="small">{{ $run->started_at?->format('d M Y H:i') ?? '—' }}</td>
                                <td class="small">{{ $formatDuration($run->duration_ms) }}</td>
                                <td class="small text-muted">{{ optional($run->error) ? str($run->error)->limit(80) : '-' }}</td>
                            </tr>
                        @empty
                            @include('layouts.admin._empty', [
                                'icon' => 'bi-clock-history',
                                'message' => 'No cron runs recorded yet',
                                'hint' => 'Run history will appear here the next time a scheduled job executes.',
                                'colspan' => 5,
                            ])
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-3">{{ $runs->links() }}</div>
        </div>
    </div>
@endsection