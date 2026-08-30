@extends('layouts.admin.app')

@section('title', 'Guests')

@section('content')
    <div class="ch-page-header">
        <div>
            <div class="ch-breadcrumb">Management / Guests</div>
            <h4>Guests</h4>
            <p class="ch-subtitle">{{ $guests->total() }} guest{{ $guests->total() === 1 ? '' : 's' }} in the directory</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @can('guests.create')
                <a href="{{ route('admin.guests.create') }}" class="btn btn-ch-primary"><i class="bi bi-plus-lg me-1"></i>New guest</a>
            @endcan
        </div>
    </div>

    <div class="ch-toolbar mb-3">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-12 col-md-6">
                <div class="position-relative">
                    <i class="bi bi-search position-absolute top-50 translate-middle-y ms-3 text-muted"></i>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control ps-5" placeholder="Search name, email, phone...">
                </div>
            </div>
            <div class="col-12 col-md-4 d-flex gap-2">
                <button class="btn btn-ch-primary"><i class="bi bi-funnel me-1"></i>Apply filters</button>
                @if (request('search'))
                    <a href="{{ route('admin.guests.index') }}" class="btn btn-light">Clear</a>
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
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Bookings</th>
                            <th>Source</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($guests as $guest)
                            <tr>
                                <td class="fw-semibold">
                                    <a href="{{ route('admin.guests.show', $guest) }}" class="text-decoration-none">{{ $guest->full_name }}</a>
                                </td>
                                <td>{{ $guest->email ?? '-' }}</td>
                                <td>{{ $guest->phone ?? '-' }}</td>
                                <td><span class="ch-badge ch-badge-muted">{{ $guest->reservations_count }}</span></td>
                                <td>{{ $guest->source ?? '-' }}</td>
                                <td>
                                    <span class="ch-badge ch-badge-{{ $guest->status === 'active' ? 'success' : ($guest->status === 'blacklisted' ? 'danger' : 'muted') }}">
                                        <span class="dot"></span>{{ ucfirst($guest->status) }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.guests.show', $guest) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                                </td>
                            </tr>
                            @empty
                                @include('layouts.admin._empty', [
                                    'icon' => 'bi-people',
                                    'message' => 'No guests found',
                                    'hint' => request('search') ? 'Try adjusting your search.' : 'Guests are added automatically when bookings are made, or you can add one manually.',
                                    'colspan' => 7,
                                    'actionUrl' => auth()->user()->can('guests.create') ? route('admin.guests.create') : null,
                                    'actionLabel' => 'Add a guest',
                                ])
                            @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-3">{{ $guests->links() }}</div>
@endsection
