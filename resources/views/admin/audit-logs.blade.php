@extends('layouts.admin.app')

@section('title', 'Audit Logs')

@section('content')
    <div class="ch-page-header">
        <div>
            <div class="ch-breadcrumb">System / Audit Logs</div>
            <h4>Audit Logs</h4>
            <p class="ch-subtitle">{{ $logs->total() }} recorded event{{ $logs->total() === 1 ? '' : 's' }}</p>
        </div>
    </div>

    <div class="ch-toolbar mb-3">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-3 col-6">
                <select name="module" class="form-select">
                    <option value="">All Modules</option>
                    @foreach ($modules as $module)
                        <option value="{{ $module }}" @selected(request('module') === $module)>{{ $module }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-5 col-6">
                <div class="position-relative">
                    <i class="bi bi-search position-absolute top-50 translate-middle-y ms-3 text-muted"></i>
                    <input type="text" name="action" value="{{ request('action') }}" class="form-control ps-5" placeholder="Search action...">
                </div>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-ch-primary"><i class="bi bi-funnel me-1"></i>Apply filters</button>
                @if (request('module') || request('action'))
                    <a href="{{ route('admin.audit-logs') }}" class="btn btn-light">Clear</a>
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
                            <th>When</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Module</th>
                            <th>Record</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($logs as $log)
                            <tr>
                                <td class="small">{{ $log->created_at->format('d M Y H:i') }}</td>
                                <td>{{ $log->user?->name ?? 'System' }}</td>
                                <td><span class="ch-badge ch-badge-muted">{{ $log->action }}</span></td>
                                <td>{{ $log->module ?? '-' }}</td>
                                <td class="small">{{ $log->record_type }}#{{ $log->record_id ?? '-' }}</td>
                                <td class="small text-muted">{{ $log->ip_address ?? '-' }}</td>
                            </tr>
                        @empty
                            @include('layouts.admin._empty', [
                                'icon' => 'bi-shield-check',
                                'message' => 'No audit logs found',
                                'hint' => 'Activity across the platform will be recorded here automatically.',
                                'colspan' => 6,
                            ])
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-3">{{ $logs->links() }}</div>
        </div>
    </div>
@endsection
