@extends('layouts.admin.app')

@section('title', 'Room Management')

@section('content')
    <div class="ch-page-header">
        <div>
            <div class="ch-breadcrumb"><a href="{{ route('admin.dashboard') }}">Dashboard</a> / Room Management</div>
            <h4>Room Management</h4>
            <p class="ch-subtitle">{{ $rooms->count() }} room{{ $rooms->count() === 1 ? '' : 's' }} across {{ $properties->count() }} propert{{ $properties->count() === 1 ? 'y' : 'ies' }}</p>
        </div>
        @can('rooms.create')
            @if ($properties->isNotEmpty())
                <div class="dropdown">
                    <button class="btn btn-ch-primary dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="bi bi-plus-lg me-1"></i>New room
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        @foreach ($properties as $property)
                            <li>
                                <a class="dropdown-item" href="{{ route('admin.rooms.create', $property) }}">
                                    <i class="bi bi-building me-2"></i>{{ $property->name }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        @endcan
    </div>

    <form method="GET" action="{{ route('admin.rooms.manage') }}" class="ch-toolbar mb-3">
        <div class="row g-2 align-items-center">
            <div class="col-12 col-md-4">
                <div class="position-relative">
                    <i class="bi bi-search position-absolute top-50 translate-middle-y ms-3 text-muted"></i>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control ps-5" placeholder="Search room name or type...">
                </div>
            </div>
            <div class="col-6 col-md-2">
                <select name="status" class="form-select">
                    <option value="">All statuses</option>
                    @foreach (['active', 'inactive', 'maintenance'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-4 d-flex gap-2">
                <button class="btn btn-ch-primary"><i class="bi bi-funnel me-1"></i>Apply filters</button>
                @if (request('search') || request('status'))
                    <a href="{{ route('admin.rooms.manage') }}" class="btn btn-light">Clear</a>
                @endif
            </div>
        </div>
    </form>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Room</th>
                            <th>Property</th>
                            <th>Type</th>
                            <th class="text-center">Capacity</th>
                            <th>Base rate</th>
                            <th>Min stay</th>
                            <th class="text-center">Images</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rooms as $room)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        @if ($room->images->isNotEmpty())
                                            <img src="{{ asset('storage/'.$room->images->first()->path) }}" alt="{{ $room->name }}" class="rounded" style="width: 44px; height: 32px; object-fit: cover;">
                                        @else
                                            <span class="ch-avatar"><i class="bi bi-door-open"></i></span>
                                        @endif
                                        <a href="{{ route('admin.rooms.show', $room) }}" class="fw-semibold text-decoration-none">{{ $room->name }}</a>
                                    </div>
                                </td>
                                <td class="text-muted" title="{{ $room->property?->status ?? 'Unassigned' }}">
                                    @if ($room->property)
                                        <i class="bi bi-building me-1"></i>{{ $room->property->name }}
                                    @else
                                        <i class="bi bi-dash me-1"></i>Unassigned
                                    @endif
                                </td>
                                <td>{{ $room->type ?? '-' }}</td>
                                <td class="text-center">{{ $room->capacity ?? '-' }}</td>
                                <td>&pound;{{ number_format($room->base_rate, 2) }}</td>
                                <td>{{ $room->min_stay ?? '-' }}</td>
                                <td class="text-center"><span class="ch-badge ch-badge-muted">{{ $room->images_count }}</span></td>
                                <td>
                                    <span class="ch-badge ch-badge-{{ $room->status === 'active' ? 'success' : ($room->status === 'maintenance' ? 'warning' : 'muted') }}">
                                        <span class="dot"></span>{{ ucfirst($room->status) }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.rooms.show', $room) }}" class="btn btn-sm btn-outline-info" title="View"><i class="bi bi-eye"></i></a>
                                    @can('rooms.update')
                                        <a href="{{ route('admin.rooms.edit', $room) }}" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                                    @endcan
                                    @can('rooms.delete')
                                        <form method="POST" action="{{ route('admin.rooms.destroy', $room) }}" class="d-inline" onsubmit="return confirm('Delete this room?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            @include('layouts.admin._empty', [
                                'icon' => 'bi-door-open',
                                'message' => 'No rooms match your filters',
                                'hint' => 'Try clearing the filters, or add a new room to get started.',
                                'colspan' => 9,
                            ])
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
