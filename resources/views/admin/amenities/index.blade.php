@extends('layouts.admin.app')

@section('title', 'Amenities')

@section('content')
    <div class="ch-page-header">
        <div>
            <div class="ch-breadcrumb">Management / Amenities</div>
            <h4>Amenities</h4>
            <p class="ch-subtitle">{{ $amenities->total() }} amenit{{ $amenities->total() === 1 ? 'y' : 'ies' }} defined</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @can('amenities.create')
                <a href="{{ route('admin.amenities.create') }}" class="btn btn-ch-primary"><i class="bi bi-plus-lg me-1"></i>New amenity</a>
            @endcan
        </div>
    </div>

    <div class="ch-toolbar mb-3">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-12 col-md-3">
                <div class="position-relative">
                    <i class="bi bi-search position-absolute top-50 translate-middle-y ms-3 text-muted"></i>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control ps-5" placeholder="Search name...">
                </div>
            </div>
            <div class="col-6 col-md-3">
                <select name="category" class="form-select">
                    <option value="">All categories</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat }}" @selected(request('category') === $cat)>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <select name="status" class="form-select">
                    <option value="">All statuses</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                </select>
            </div>
            <div class="col-12 col-md-3 d-flex gap-2">
                <button class="btn btn-ch-primary"><i class="bi bi-funnel me-1"></i>Filter</button>
                @if (request('search') || request('category') || request('status'))
                    <a href="{{ route('admin.amenities.index') }}" class="btn btn-light">Clear</a>
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
                            <th>Name</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Properties</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($amenities as $amenity)
                            <tr>
                                <td class="fw-semibold">
                                    <i class="bi {{ $amenity->icon ?? 'bi-check-circle' }} me-2 text-muted"></i>{{ $amenity->name }}
                                </td>
                                <td>
                                    @if ($amenity->category)
                                        <span class="ch-badge ch-badge-muted">{{ $amenity->category }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-muted">{{ \Illuminate\Support\Str::limit($amenity->description, 60) ?? '—' }}</td>
                                <td>
                                    @can('amenities.update')
                                        <form method="POST" action="{{ route('admin.amenities.toggle', $amenity) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm {{ $amenity->is_active ? 'btn-outline-success' : 'btn-outline-secondary' }}" title="{{ $amenity->is_active ? 'Deactivate' : 'Activate' }}">
                                                <i class="bi {{ $amenity->is_active ? 'bi-toggle-on' : 'bi-toggle-off' }}"></i>
                                                <span class="d-none d-md-inline ms-1">{{ $amenity->is_active ? 'Active' : 'Inactive' }}</span>
                                            </button>
                                        </form>
                                    @else
                                        <span class="ch-badge ch-badge-{{ $amenity->is_active ? 'success' : 'muted' }}">
                                            <span class="dot"></span>{{ $amenity->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    @endcan
                                </td>
                                <td><span class="ch-badge ch-badge-muted">{{ $amenity->properties_count }}</span></td>
                                <td class="text-end">
                                    @can('amenities.update')
                                        <a href="{{ route('admin.amenities.edit', $amenity) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                    @endcan
                                    @can('amenities.delete')
                                        <form method="POST" action="{{ route('admin.amenities.destroy', $amenity) }}" class="d-inline" onsubmit="return confirm('Delete this amenity?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            @include('layouts.admin._empty', [
                                'icon' => 'bi-plus-circle',
                                'message' => 'No amenities yet',
                                'hint' => 'Amenities describe the comforts and features of your property. Add some to get started.',
                                'colspan' => 6,
                                'actionUrl' => auth()->user()->can('amenities.create') ? route('admin.amenities.create') : null,
                                'actionLabel' => 'Add an amenity',
                            ])
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-3">{{ $amenities->links() }}</div>
@endsection
