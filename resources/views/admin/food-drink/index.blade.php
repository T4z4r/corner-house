@extends('layouts.admin.app')

@section('title', 'Food & Drink')

@section('content')
    <div class="ch-page-header">
        <div>
            <div class="ch-breadcrumb">Management / Food & Drink</div>
            <h4>Food & Drink</h4>
            <p class="ch-subtitle">{{ $items->count() }} {{ Str::plural('item', $items->count()) }} listed</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @can('food-drink.create')
                <a href="{{ route('admin.food-drink.create') }}" class="btn btn-ch-primary"><i class="bi bi-plus-lg me-1"></i>Add New</a>
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
                    @foreach ($categories ?? [] as $cat)
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
                    <a href="{{ route('admin.food-drink.index') }}" class="btn btn-light">Clear</a>
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
                            <th>Featured</th>
                            <th>Status</th>
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
                                <td>
                                    @can('food-drink.update')
                                        <form method="POST" action="{{ route('admin.food-drink.toggle', $item) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm {{ $item->is_featured ? 'btn-outline-warning' : 'btn-outline-secondary' }}" title="{{ $item->is_featured ? 'Remove featured' : 'Mark as featured' }}">
                                                <i class="bi {{ $item->is_featured ? 'bi-star-fill' : 'bi-star' }}"></i>
                                            </button>
                                        </form>
                                    @else
                                        <span class="ch-badge ch-badge-{{ $item->is_featured ? 'warning' : 'muted' }}">
                                            <i class="bi bi-star{{ $item->is_featured ? '-fill' : '' }}"></i>
                                        </span>
                                    @endcan
                                </td>
                                <td>
                                    @can('food-drink.update')
                                        <form method="POST" action="{{ route('admin.food-drink.toggle', $item) }}" class="d-inline">
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
                                    @can('food-drink.update')
                                        <a href="{{ route('admin.food-drink.edit', $item) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                    @endcan
                                    @can('food-drink.delete')
                                        <form method="POST" action="{{ route('admin.food-drink.destroy', $item) }}" class="d-inline" onsubmit="return confirm('Delete this item?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            @include('layouts.admin._empty', [
                                'icon' => 'bi-cup-straw',
                                'message' => 'No food & drink listings yet',
                                'hint' => 'Add local restaurants, cafes, pubs, and more to help guests discover the area.',
                                'colspan' => 5,
                                'actionUrl' => auth()->user()->can('food-drink.create') ? route('admin.food-drink.create') : null,
                                'actionLabel' => 'Add a listing',
                            ])
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-3">{{ $items->links() }}</div>
@endsection
