@extends('layouts.admin.app')

@section('title', 'Places of Interest')

@section('content')
    <div class="ch-page-header">
        <div>
            <div class="ch-breadcrumb">Management / Places of Interest</div>
            <h4>Places of Interest</h4>
            <p class="ch-subtitle">{{ $items->total() }} place{{ $items->total() === 1 ? '' : 's' }} defined</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @can('places.create')
                <a href="{{ route('admin.places.create') }}" class="btn btn-ch-primary"><i class="bi bi-plus-lg me-1"></i>Add New</a>
            @endcan
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Distance</th>
                            <th>Active</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $item)
                            <tr>
                                <td class="fw-semibold">{{ $item->name }}</td>
                                <td>
                                    @if ($item->category)
                                        <span class="ch-badge ch-badge-muted">{{ $item->category }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-muted">{{ $item->distance ?? '—' }}</td>
                                <td>
                                    @can('places.update')
                                        <form method="POST" action="{{ route('admin.places.toggle', $item) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm {{ $item->is_active ? 'btn-outline-success' : 'btn-outline-secondary' }}" title="{{ $item->is_active ? 'Deactivate' : 'Activate' }}">
                                                <i class="bi {{ $item->is_active ? 'bi-toggle-on' : 'bi-toggle-off' }}"></i>
                                                <span class="d-none d-md-inline ms-1">{{ $item->is_active ? 'Active' : 'Inactive' }}</span>
                                            </button>
                                        </form>
                                    @else
                                        <span class="ch-badge ch-badge-{{ $item->is_active ? 'success' : 'muted' }}">
                                            <span class="dot"></span>{{ $item->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    @endcan
                                </td>
                                <td class="text-end">
                                    @can('places.update')
                                        <a href="{{ route('admin.places.edit', $item) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                    @endcan
                                    @can('places.delete')
                                        <form method="POST" action="{{ route('admin.places.destroy', $item) }}" class="d-inline" onsubmit="return confirm('Delete this place?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            @include('layouts.admin._empty', [
                                'icon' => 'bi-geo-alt',
                                'message' => 'No places of interest yet',
                                'hint' => 'Add local attractions, shops, and points of interest for your guests.',
                                'colspan' => 5,
                                'actionUrl' => auth()->user()->can('places.create') ? route('admin.places.create') : null,
                                'actionLabel' => 'Add a place',
                            ])
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-3">{{ $items->links() }}</div>
@endsection
