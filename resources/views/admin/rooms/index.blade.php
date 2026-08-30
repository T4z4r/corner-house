@extends('layouts.admin.app')

@section('title', 'Rooms')

@section('content')
    <div class="ch-page-header">
        <div>
            <div class="ch-breadcrumb"><a href="{{ route('admin.properties.index') }}">Properties</a> / Rooms</div>
            <h4>Rooms — {{ $property->name }}</h4>
            <p class="ch-subtitle">{{ $rooms->count() }} room{{ $rooms->count() === 1 ? '' : 's' }} in this property</p>
        </div>
        @can('rooms.create')
            <a href="{{ route('admin.rooms.create', ['property' => $property]) }}" class="btn btn-ch-primary">
                <i class="bi bi-plus-lg me-1"></i>New room
            </a>
        @endcan
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Capacity</th>
                            <th>Base rate</th>
                            <th>Min stay</th>
                            <th>Images</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rooms as $room)
                            <tr>
                <td class="fw-semibold">
                    <a href="{{ route('admin.rooms.show', $room) }}" class="text-decoration-none">{{ $room->name }}</a>
                </td>
                                <td>{{ $room->type ?? '-' }}</td>
                                <td>{{ $room->capacity }}</td>
                                <td>&pound;{{ number_format($room->base_rate, 2) }}</td>
                                <td>{{ $room->min_stay }}</td>
                                <td><span class="ch-badge ch-badge-muted">{{ $room->images_count }}</span></td>
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
                                </td>
                            </tr>
                                @empty
                                    @include('layouts.admin._empty', [
                                        'icon' => 'bi-door-open',
                                        'message' => 'No rooms yet',
                                        'hint' => 'Add a room to this property to start taking bookings.',
                                        'colspan' => 6,
                                        'actionUrl' => auth()->user()->can('rooms.create') ? route('admin.rooms.create', $property) : null,
                                        'actionLabel' => 'Add a room',
                                    ])
                                @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
