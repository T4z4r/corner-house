@extends('layouts.admin.app')

@section('title', 'Add-Ons')

@section('content')
    <div class="ch-page-header">
        <div>
            <div class="ch-breadcrumb">Management / Add-Ons</div>
            <h4>Add-Ons</h4>
            <p class="ch-subtitle">{{ $items->count() }} add-on{{ $items->count() === 1 ? '' : 's' }} defined</p>
        </div>
        @can('addons.create')
            <a href="{{ route('admin.addons.create') }}" class="btn btn-ch-primary"><i class="bi bi-plus-lg me-1"></i>Add new</a>
        @endcan
    </div>

    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Price (&pound;)</th>
                            <th>Unit</th>
                            <th>Active</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $item)
                            <tr>
                                <td class="fw-semibold">{{ $item->name }}</td>
                                <td><span class="ch-badge ch-badge-muted">{{ ucfirst($item->category) }}</span></td>
                                <td>&pound;{{ number_format($item->price, 2) }}</td>
                                <td>{{ $item->unit ?? '-' }}</td>
                                <td>
                                    @can('addons.update')
                                        <form method="POST" action="{{ route('admin.addons.toggle', $item) }}" class="d-inline">
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
                                    @can('addons.update')
                                        <a href="{{ route('admin.addons.edit', $item) }}" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                                    @endcan
                                    @can('addons.delete')
                                        <form method="POST" action="{{ route('admin.addons.destroy', $item) }}" class="d-inline" onsubmit="return confirm('Delete this add-on?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            @include('layouts.admin._empty', [
                                'icon' => 'bi-plus-circle',
                                'message' => 'No add-ons yet',
                                'hint' => 'Add-ons let guests customise their stay. Create one to get started.',
                                'colspan' => 6,
                                'actionUrl' => auth()->user()->can('addons.create') ? route('admin.addons.create') : null,
                                'actionLabel' => 'Add an add-on',
                            ])
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-3">{{ $items->links() }}</div>
@endsection
