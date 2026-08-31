@extends('layouts.admin.app')
@section('title', 'Payment Details')
@section('content')
<div class="ch-page-header">
    <div>
        <div class="ch-breadcrumb">Finance / Payments</div>
        <h4>Payment #{{ $payment->id }}</h4>
        <p class="ch-subtitle">{{ $payment->reservation?->reference ?? '-' }}</p>
    </div>
    <a href="{{ route('admin.payments.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                Payment details
                @if ($payment->status === 'paid')
                    <span class="badge bg-success">Paid</span>
                @elseif ($payment->status === 'pending')
                    <span class="badge bg-warning text-dark">Pending</span>
                @elseif ($payment->status === 'refunded')
                    <span class="badge bg-danger">Refunded</span>
                @else
                    <span class="badge bg-secondary">{{ ucfirst($payment->status) }}</span>
                @endif
            </div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr><td class="text-muted" style="width:200px;">Amount</td><td class="fw-semibold fs-5">£{{ number_format($payment->amount, 2) }} <span class="text-muted small">{{ $payment->currency }}</span></td></tr>
                    <tr><td class="text-muted">Provider</td><td><span class="badge bg-primary">{{ ucfirst($payment->provider) }}</span></td></tr>
                    <tr><td class="text-muted">Payment method</td><td>{{ ucfirst($payment->method ?? 'card') }}</td></tr>
                    <tr><td class="text-muted">Paid at</td><td>{{ $payment->paid_at?->format('d M Y H:i:s') ?? 'Not yet paid' }}</td></tr>
                    <tr><td class="text-muted">Created</td><td>{{ $payment->created_at?->format('d M Y H:i:s') ?? '-' }}</td></tr>
                </table>
            </div>
        </div>

        @if ($payment->provider === 'stripe')
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white">Stripe details</div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tr>
                            <td class="text-muted" style="width:200px;">Session ID</td>
                            <td>
                                @if ($payment->provider_session_id)
                                    <code class="small">{{ $payment->provider_session_id }}</code>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Payment Intent</td>
                            <td>
                                @if ($payment->provider_payment_id)
                                    <code class="small">{{ $payment->provider_payment_id }}</code>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        @endif

        @if ($payment->reservation)
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white">Reservation</div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tr><td class="text-muted" style="width:200px;">Reference</td><td>{{ $payment->reservation->reference }}</td></tr>
                        <tr><td class="text-muted">Room</td><td>{{ $payment->reservation->room?->name ?? '-' }}</td></tr>
                        <tr><td class="text-muted">Guest</td><td>{{ $payment->reservation->guest?->full_name ?? '-' }}</td></tr>
                        <tr><td class="text-muted">Check-in</td><td>{{ $payment->reservation->check_in?->format('d M Y') ?? '-' }}</td></tr>
                        <tr><td class="text-muted">Check-out</td><td>{{ $payment->reservation->check_out?->format('d M Y') ?? '-' }}</td></tr>
                        <tr><td class="text-muted">Status</td><td>{{ ucfirst($payment->reservation->status) }}</td></tr>
                    </table>
                </div>
            </div>
        @endif

        @if ($payment->refunds->isNotEmpty())
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white">Refunds</div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Amount</th><th>Status</th><th>Reason</th><th>Date</th></tr></thead>
                        <tbody>
                            @foreach ($payment->refunds as $refund)
                                <tr>
                                    <td>£{{ number_format($refund->amount, 2) }}</td>
                                    <td><span class="badge {{ $refund->status === 'succeeded' ? 'bg-success' : 'bg-warning' }}">{{ ucfirst($refund->status) }}</span></td>
                                    <td>{{ $refund->reason ?? '-' }}</td>
                                    <td>{{ $refund->created_at?->format('d M Y H:i') ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

    <div class="col-lg-4">
        @can('payments.refund')
            @if ($payment->status === 'paid')
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white">Process refund</div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.payments.refund', $payment) }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Amount (£)</label>
                                <input type="number" step="0.01" name="amount" class="form-control" value="{{ $payment->amount }}" max="{{ $payment->amount }}" min="0.01">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Reason</label>
                                <input type="text" name="reason" class="form-control" placeholder="Optional reason">
                            </div>
                            <button class="btn btn-warning w-100" onclick="return confirm('Process this refund?')"><i class="bi bi-arrow-return-left me-1"></i>Refund</button>
                        </form>
                    </div>
                </div>
            @endif
        @endcan
    </div>
</div>
@endsection
