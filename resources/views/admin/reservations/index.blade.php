@extends('layouts.admin.app')

@section('title', 'Bookings')

@section('content')
    <div class="ch-page-header">
        <div>
            <div class="ch-breadcrumb">Management / Bookings</div>
            <h4>Bookings</h4>
            <p class="ch-subtitle">{{ $reservations->total() }} booking{{ $reservations->total() === 1 ? '' : 's' }}</p>
        </div>
        @can('reservations.create')
            <a href="{{ route('admin.reservations.create') }}" class="btn btn-ch-primary"><i class="bi bi-plus-lg me-1"></i>New booking</a>
        @endcan
    </div>

    <div class="ch-toolbar mb-3">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-12 col-md-4">
                <div class="position-relative">
                    <i class="bi bi-search position-absolute top-50 translate-middle-y ms-3 text-muted"></i>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control ps-5" placeholder="Search reference or guest...">
                </div>
            </div>
            <div class="col-6 col-md-2">
                <select name="status" class="form-select">
                    <option value="">All statuses</option>
                    @foreach (['pending', 'hold', 'confirmed', 'checked_in', 'checked_out', 'cancelled', 'no_show'] as $s)
                        <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <select name="source" class="form-select">
                    <option value="">All sources</option>
                    @foreach (['direct', 'manual', 'airbnb', 'booking.com', 'vrbo'] as $s)
                        <option value="{{ $s }}" @selected(request('source') === $s)>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-4 d-flex gap-2">
                <button class="btn btn-ch-primary"><i class="bi bi-funnel me-1"></i>Apply filters</button>
                @if (request('status') || request('source') || request('search'))
                    <a href="{{ route('admin.reservations.index') }}" class="btn btn-light">Clear</a>
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
                            <th>Reference</th>
                            <th>Guest</th>
                            <th>Room</th>
                            <th>Dates</th>
                            <th>Total</th>
                            <th>Source</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($reservations as $reservation)
                            <tr>
                                <td class="fw-semibold">{{ $reservation->reference }}</td>
                                <td>{{ $reservation->guest?->full_name ?? 'No guest' }}</td>
                                <td>{{ $reservation->room?->name ?? '-' }}</td>
                                <td class="small">{{ $reservation->check_in->format('d M Y') }} → {{ $reservation->check_out->format('d M Y') }}</td>
                                <td>&pound;{{ number_format($reservation->total_amount, 2) }}</td>
                                <td><span class="ch-badge ch-badge-muted">{{ ucfirst($reservation->source) }}</span></td>
                                <td>
                                    <span class="ch-badge ch-badge-{{ $reservation->payment_status === 'paid' ? 'success' : ($reservation->payment_status === 'partial' ? 'warning' : 'muted') }}">
                                        <span class="dot"></span>{{ ucfirst($reservation->payment_status) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="ch-badge ch-badge-{{ $reservation->status === 'confirmed' || $reservation->status === 'checked_in' || $reservation->status === 'checked_out' ? 'success' : ($reservation->status === 'cancelled' || $reservation->status === 'no_show' ? 'danger' : 'warning') }}">
                                        <span class="dot"></span>{{ ucfirst(str_replace('_', ' ', $reservation->status)) }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.reservations.show', $reservation) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                                </td>
                            </tr>
                            @empty
                                @include('layouts.admin._empty', [
                                    'icon' => 'bi-journal-bookmark',
                                    'message' => 'No bookings found',
                                    'hint' => request('status') || request('source') || request('search') ? 'Try clearing or adjusting your filters.' : 'Bookings will appear here once guests reserve a room.',
                                    'colspan' => 9,
                                    'actionUrl' => auth()->user()->can('reservations.create') ? route('admin.reservations.create') : null,
                                    'actionLabel' => 'New booking',
                                ])
                            @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-3">{{ $reservations->links() }}</div>
@endsection
