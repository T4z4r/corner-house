@extends('layouts.admin.app')

@section('title', 'Properties')

@section('content')
    <div class="ch-page-header">
        <div>
            <div class="ch-breadcrumb">Management / Properties</div>
            <h4>Properties</h4>
            <p class="ch-subtitle">{{ $properties->total() }} propert{{ $properties->total() === 1 ? 'y' : 'ies' }} on the platform</p>
        </div>
        @can('properties.create')
            <a href="{{ route('admin.properties.create') }}" class="btn btn-ch-primary">
                <i class="bi bi-plus-lg me-1"></i>New property
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
                            <th>Location</th>
                            <th>Rooms</th>
                            <th>Capacity</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($properties as $property)
                            <tr>
                                <td class="fw-semibold">
                                    <a href="{{ route('admin.properties.show', $property) }}" class="text-decoration-none">{{ $property->name }}</a>
                                </td>
                                <td>{{ $property->city }}, {{ $property->country }}</td>
                                <td>
                                    <span class="ch-badge ch-badge-muted">{{ $property->rooms_count }}</span>
                                </td>
                                <td>{{ $property->capacity }}</td>
                                <td>
                                    <span class="ch-badge ch-badge-{{ $property->status === 'active' ? 'success' : ($property->status === 'maintenance' ? 'warning' : 'muted') }}">
                                        <span class="dot"></span>{{ ucfirst($property->status) }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    @can('rooms.view')
                                        <a href="{{ route('admin.rooms.index', $property) }}" class="btn btn-sm btn-outline-secondary"
                                           title="Rooms"><i class="bi bi-door-open"></i></a>
                                    @endcan
                                    <a href="{{ route('admin.properties.show', $property) }}" class="btn btn-sm btn-outline-info" title="View"><i class="bi bi-eye"></i></a>
                                    @can('properties.update')
                                        <a href="{{ route('admin.properties.edit', $property) }}" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            @include('layouts.admin._empty', [
                                'icon' => 'bi-building',
                                'message' => 'No properties yet',
                                'hint' => 'Add your first property to start managing rooms and bookings.',
                                'colspan' => 6,
                                'actionUrl' => auth()->user()->can('properties.create') ? route('admin.properties.create') : null,
                                'actionLabel' => 'Add a property',
                            ])
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-3">{{ $properties->links() }}</div>
@endsection
