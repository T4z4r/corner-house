@extends('layouts.admin.app')

@section('title', 'Booking '.$reservation->reference)

@section('content')
    @error('error')
        <div class="alert alert-danger">{{ $message }}</div>
    @enderror

    <div class="ch-page-header">
        <div>
            <div class="ch-breadcrumb"><a href="{{ route('admin.reservations.index') }}">Bookings</a> / {{ $reservation->reference }}</div>
            <div class="d-flex align-items-center gap-3">
                <h4 class="mb-0">{{ $reservation->reference }}</h4>
                <span class="ch-badge ch-badge-{{ $reservation->status === 'confirmed' || $reservation->status === 'checked_in' || $reservation->status === 'checked_out' ? 'success' : ($reservation->status === 'cancelled' || $reservation->status === 'no_show' ? 'danger' : 'warning') }}">
                    <span class="dot"></span>{{ ucfirst(str_replace('_', ' ', $reservation->status)) }}
                </span>
            </div>
            <p class="ch-subtitle">{{ $reservation->room?->name }} · {{ $reservation->room?->property?->name }}</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @if (in_array($reservation->status, ['confirmed', 'checked_in']))
                @can('reservations.update')
                    @if ($reservation->status !== 'checked_in')
                        <form method="POST" action="{{ route('admin.reservations.check-in', $reservation) }}">
                            @csrf
                            <button class="btn btn-outline-success btn-sm"><i class="bi bi-box-arrow-in-right me-1"></i>Check-in</button>
                        </form>
                    @endif
                    <form method="POST" action="{{ route('admin.reservations.check-out', $reservation) }}">
                        @csrf
                        <button class="btn btn-outline-info btn-sm"><i class="bi bi-box-arrow-right me-1"></i>Check-out</button>
                    </form>
                @endcan
            @endif
            @if (! in_array($reservation->status, ['cancelled', 'checked_out', 'no_show']))
                @can('reservations.cancel')
                    <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#cancelModal">
                        <i class="bi bi-x-circle me-1"></i>Cancel
                    </button>
                @endcan
            @endif
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white d-flex justify-content-between">
                    <h6 class="mb-0"><i class="bi bi-calendar-check me-2 text-muted"></i>Stay details</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3">
                            <div class="text-muted small">Check-in</div>
                            <div class="fw-bold">{{ $reservation->check_in->format('D, d M Y') }}</div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted small">Check-out</div>
                            <div class="fw-bold">{{ $reservation->check_out->format('D, d M Y') }}</div>
                        </div>
                        <div class="col-md-2">
                            <div class="text-muted small">Nights</div>
                            <div class="fw-bold">{{ $reservation->check_in->diffInDays($reservation->check_out) }}</div>
                        </div>
                        <div class="col-md-2">
                            <div class="text-muted small">Guests</div>
                            <div class="fw-bold">{{ $reservation->guests_count }}</div>
                        </div>
                        <div class="col-md-2">
                            <div class="text-muted small">Source</div>
                            <div class="fw-bold">{{ ucfirst($reservation->source) }}</div>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="ch-detail-label">Property</div>
                            <div class="mb-1">{{ $reservation->property?->name }}</div>
                            <div class="ch-detail-label mt-3">Room</div>
                            <div>{{ $reservation->room?->name }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="ch-detail-label">Guest</div>
                            <div class="mb-1">{{ $reservation->guest?->full_name ?? 'No guest linked' }}</div>
                            <div class="ch-detail-label mt-3">Contact</div>
                            <div>{{ $reservation->guest?->email }} {{ $reservation->guest?->phone }}</div>
                        </div>
                    </div>
                    @if ($reservation->notes)
                        <hr><strong>Notes:</strong> {{ $reservation->notes }}
                    @endif
                </div>
            </div>

            @if($reservation->guests->isNotEmpty())
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white"><h6 class="mb-0">Additional guests</h6></div>
                    <ul class="list-group list-group-flush">
                        @foreach ($reservation->guests as $rg)
                            <li class="list-group-item">{{ $rg->first_name }} {{ $rg->last_name }} <span class="ch-badge ch-badge-muted">{{ $rg->type }}</span></li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white"><h6 class="mb-0">Payment summary</h6></div>
                <div class="card-body">
                    <table class="table table-sm mb-3">
                        <tr><td class="text-muted">Base</td><td class="text-end">&pound;{{ number_format($reservation->base_amount, 2) }}</td></tr>
                        <tr><td class="text-muted">Discount</td><td class="text-end">-&pound;{{ number_format($reservation->discount_amount, 2) }}</td></tr>
                        <tr><td class="text-muted">Tax</td><td class="text-end">&pound;{{ number_format($reservation->tax_amount, 2) }}</td></tr>
                        <tr><td class="text-muted">Fees</td><td class="text-end">&pound;{{ number_format($reservation->fees_amount, 2) }}</td></tr>
                        <tr class="border-top"><td class="fw-bold">Total</td><td class="text-end fw-bold">&pound;{{ number_format($reservation->total_amount, 2) }}</td></tr>
                        <tr><td class="text-muted">Paid</td><td class="text-end">&pound;{{ number_format($reservation->paid_amount, 2) }}</td></tr>
                    </table>
                    <span class="ch-badge ch-badge-{{ $reservation->payment_status === 'paid' ? 'success' : ($reservation->payment_status === 'partial' ? 'warning' : 'muted') }}">
                        <i class="bi bi-credit-card me-1"></i>Payment: {{ ucfirst($reservation->payment_status) }}
                    </span>
                    <hr>
                    <div class="small">
                        <div class="ch-detail-label mb-1">Channel sync</div>
                        <span class="ch-badge ch-badge-{{ $reservation->sync_status === 'synced' ? 'success' : ($reservation->sync_status === 'failed' ? 'danger' : 'muted') }}">
                            <span class="dot"></span>{{ ucfirst($reservation->sync_status) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (! in_array($reservation->status, ['cancelled', 'checked_out', 'no_show']))
        <div class="modal fade" id="cancelModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" action="{{ route('admin.reservations.cancel', $reservation) }}" class="modal-content">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Cancel booking</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <label class="form-label">Reason (optional)</label>
                        <textarea name="reason" class="form-control" rows="3" placeholder="Guest request, no-show, etc."></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Back</button>
                        <button class="btn btn-danger">Confirm cancellation</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection
