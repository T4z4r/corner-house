@extends('layouts.admin.app')
@section('title', 'Payment')
@section('content')
<div class="ch-page-header"><div><h4>Payment #{{ $payment->id }}</h4></div></div>
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <p>Reservation: {{ $payment->reservation?->reference }}</p>
        <p>Amount: £{{ number_format($payment->amount, 2) }} {{ $payment->currency }}</p>
        <p>Status: {{ $payment->status }}</p>
        @can('payments.refund')
            @if ($payment->status === 'paid')
                <form method="POST" action="{{ route('admin.payments.refund', $payment) }}" class="row g-2">
                    @csrf
                    <div class="col-md-4"><input type="number" step="0.01" name="amount" class="form-control" value="{{ $payment->amount }}"></div>
                    <div class="col-md-4"><input type="text" name="reason" class="form-control" placeholder="Reason"></div>
                    <div class="col-md-4"><button class="btn btn-warning">Refund</button></div>
                </form>
            @endif
        @endcan
    </div>
</div>
@endsection
