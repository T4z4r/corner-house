@extends('layouts.admin.app')
@section('title', 'Payments')
@section('content')
<div class="ch-page-header">
    <div>
        <div class="ch-breadcrumb">Finance / Payments</div>
        <h4>Payments</h4>
        <p class="ch-subtitle">Stripe payment history and transaction details</p>
    </div>
    @can('payments.create')
        <a href="{{ route('admin.payments.instant') }}" class="btn btn-primary">Instant Payout</a>
    @endcan
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span>Stripe account balance
            @if ($stripeBalance !== null)
                <span class="badge {{ $stripeBalance['livemode'] ? 'bg-success' : 'bg-warning text-dark' }} ms-2">{{ $stripeBalance['livemode'] ? 'Live mode' : 'Test mode' }}</span>
            @endif
        </span>
        <a href="{{ route('admin.payments.index', request()->only('status')) }}" class="btn btn-sm btn-outline-primary">Refresh balance</a>
    </div>
    <div class="card-body">
        @if ($stripeBalance === null)
            <div class="alert alert-warning mb-0" role="alert">Stripe balance is currently unavailable. Check your Stripe configuration or refresh to try again.</div>
        @else
            <ul class="nav nav-tabs" role="tablist" aria-label="Stripe balances">
                @foreach (['available' => 'Available balance', 'pending' => 'Pending balance'] as $type => $label)
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $loop->first ? 'active' : '' }}" id="balance-{{ $type }}-tab" data-bs-toggle="tab" data-bs-target="#balance-{{ $type }}" type="button" role="tab" aria-controls="balance-{{ $type }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}">{{ $label }}</button>
                    </li>
                @endforeach
            </ul>
            <div class="tab-content pt-3">
                @foreach (['available', 'pending'] as $type)
                    <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="balance-{{ $type }}" role="tabpanel" aria-labelledby="balance-{{ $type }}-tab" tabindex="0">
                        <p class="text-muted">{{ $type === 'available' ? 'Funds available for payout.' : 'Funds awaiting settlement before becoming available.' }}</p>
                        <div class="row g-3">
                            @forelse ($stripeBalance[$type] as $entry)
                                <div class="col-sm-6 col-lg-3">
                                    <div class="border rounded p-3">
                                        <div class="text-muted small">{{ $entry['currency'] }}</div>
                                        <div class="fs-4 fw-bold">{{ $entry['formatted'] }}</div>
                                    </div>
                                </div>
                            @empty
                                <p class="text-muted mb-0">No {{ $type }} balances reported by Stripe.</p>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="text-muted small mb-1">Total Revenue</div>
                <div class="fs-4 fw-bold" style="color:var(--ch-forest);">£{{ number_format($stats->total_revenue, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="text-muted small mb-1">Paid</div>
                <div class="fs-4 fw-bold text-success">{{ $stats->paid }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="text-muted small mb-1">Pending</div>
                <div class="fs-4 fw-bold text-warning">{{ $stats->pending }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="text-muted small mb-1">Refunded</div>
                <div class="fs-4 fw-bold text-danger">{{ $stats->refunded }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span>Transaction history</span>
        <div class="d-flex gap-2">
            <select class="form-select form-select-sm" style="width:auto;" onchange="window.location='{{ route('admin.payments.index') }}?status='+this.value">
                <option value="">All statuses</option>
                <option value="paid" @selected(request('status') === 'paid')>Paid</option>
                <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                <option value="refunded" @selected(request('status') === 'refunded')>Refunded</option>
                <option value="failed" @selected(request('status') === 'failed')>Failed</option>
            </select>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Reference</th>
                    <th>Guest</th>
                    <th>Amount</th>
                    <th>Provider</th>
                    <th>Session ID</th>
                    <th>Status</th>
                    <th>Paid</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($payments as $payment)
                    <tr>
                        <td>
                            <a href="{{ route('admin.payments.show', $payment) }}" class="fw-semibold text-decoration-none">
                                {{ $payment->reservation?->reference ?? '#' . $payment->id }}
                            </a>
                        </td>
                        <td>{{ $payment->guest?->full_name ?? $payment->reservation?->guest?->full_name ?? '-' }}</td>
                        <td class="fw-semibold">£{{ number_format($payment->amount, 2) }}</td>
                        <td>
                            @if ($payment->provider === 'stripe')
                                <span class="badge bg-primary">Stripe</span>
                            @else
                                <span class="badge bg-secondary">{{ ucfirst($payment->provider) }}</span>
                            @endif
                        </td>
                        <td>
                            @if ($payment->provider_session_id)
                                <code class="small" title="{{ $payment->provider_session_id }}">{{ Str::limit($payment->provider_session_id, 20, '...') }}</code>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            @if ($payment->status === 'paid')
                                <span class="badge bg-success">Paid</span>
                            @elseif ($payment->status === 'pending')
                                <span class="badge bg-warning text-dark">Pending</span>
                            @elseif ($payment->status === 'refunded')
                                <span class="badge bg-danger">Refunded</span>
                            @else
                                <span class="badge bg-secondary">{{ ucfirst($payment->status) }}</span>
                            @endif
                        </td>
                        <td>{{ $payment->paid_at?->format('d M Y H:i') ?? '-' }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.payments.show', $payment) }}" class="btn btn-sm btn-outline-primary">View</a>
                            @include('admin.payments._delete', ['payment' => $payment])
                        </td>
                    </tr>
                @empty
                    @include('layouts.admin._empty', ['icon' => 'bi-credit-card', 'message' => 'No payments found', 'hint' => 'Payments will appear here once guests start booking.', 'colspan' => 8])
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $payments->links() }}</div>
@endsection
