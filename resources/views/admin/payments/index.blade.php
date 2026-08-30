@extends('layouts.admin.app')
@section('title', 'Payments')
@section('content')
<div class="ch-page-header"><div><div class="ch-breadcrumb">Finance / Payments</div><h4>Payments</h4></div></div>
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Reservation</th><th>Amount</th><th>Status</th><th>Paid</th><th></th></tr></thead>
            <tbody>
                @forelse ($payments as $payment)
                    <tr>
                        <td>{{ $payment->reservation?->reference }}</td>
                        <td>£{{ number_format($payment->amount, 2) }}</td>
                        <td>{{ ucfirst($payment->status) }}</td>
                        <td>{{ $payment->paid_at?->format('d M Y H:i') ?? '-' }}</td>
                        <td class="text-end"><a href="{{ route('admin.payments.show', $payment) }}" class="btn btn-sm btn-outline-primary">View</a></td>
                    </tr>
                @empty
                    @include('layouts.admin._empty', ['icon' => 'bi-credit-card', 'message' => 'No payments', 'hint' => '', 'colspan' => 5])
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $payments->links() }}</div>
@endsection
