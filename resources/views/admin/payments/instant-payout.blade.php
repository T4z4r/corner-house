@extends('layouts.admin.app')
@section('title', 'Instant Payout')
@section('content')
<div class="ch-page-header">
    <div>
        <div class="ch-breadcrumb">Finance / Payments</div>
        <h4>Instant Payout</h4>
        <p class="ch-subtitle">Send eligible Stripe funds to a registered payout destination.</p>
    </div>
    <a href="{{ route('admin.payments.index') }}" class="btn btn-outline-primary">Back to payments</a>
</div>

@if ($review && ! isset($review['result']))
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h5>Review payout</h5>
            <p><strong>{{ strtoupper($review['currency']) }} {{ number_format($review['amount'] / 100, 2) }}</strong> to <strong>{{ $review['label'] }}</strong></p>
            <p class="badge {{ $review['livemode'] ? 'bg-success' : 'bg-warning text-dark' }}">{{ $review['livemode'] ? 'Live mode — real funds will be sent' : 'Test mode — no real money will move' }}</p>
            <p>Stripe charges an Instant Payout fee. <a href="https://docs.stripe.com/payouts/instant-payouts#pricing" target="_blank" rel="noopener">View Stripe fees and limits</a>. Arrival is usually within 30 minutes, subject to Stripe and your bank.</p>
            <form method="POST" action="{{ route('admin.payments.instant.store') }}">
                @csrf
                <input type="hidden" name="review_key" value="{{ $review['key'] }}">
                <label class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="confirmation" value="1" required>
                    <span class="form-check-label">I confirm this amount and destination and authorise the payout, including applicable Stripe fees.</span>
                </label>
                <button type="submit" class="btn btn-primary">Confirm Instant Payout</button>
            </form>
        </div>
    </div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-body">
        @if ($options === null)
            <div class="alert alert-warning">Unable to load payout eligibility. Check your Stripe configuration and permissions, then refresh this page.</div>
        @elseif ($options['destinations'] === [])
            <div class="alert alert-info">No eligible destination with an instant payout balance is available. Check eligibility and add a supported debit card or bank account in Stripe.</div>
        @else
            <h5>Choose amount and destination</h5>
            <p class="text-muted">{{ $options['livemode'] ? 'Live Stripe account' : 'Test Stripe account' }}. Amounts shown are the card-funded balance currently eligible for Instant Payouts. Stripe rechecks its fees and limits when processing.</p>
            <form method="POST" action="{{ route('admin.payments.instant.review') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label" for="payoutDestination">Destination and available balance</label>
                    <select class="form-select" id="payoutDestination" name="destination" required>
                        @foreach ($options['destinations'] as $destination)
                            <option value="{{ $destination['id'] }}" @selected(old('destination') === $destination['id'])>{{ $destination['label'] }} — {{ strtoupper($destination['currency']) }} {{ number_format($destination['available'] / 100, 2) }} available</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="payoutAmount">Amount in the destination currency</label>
                    <input type="number" class="form-control" id="payoutAmount" name="amount" min="0.01" step="0.01" value="{{ old('amount') }}" required>
                </div>
                <button type="submit" class="btn btn-outline-primary">Review payout</button>
            </form>
        @endif
        <a href="https://dashboard.stripe.com/settings/money-management" target="_blank" rel="noopener" class="d-inline-block mt-3">Manage payout destinations in Stripe</a>
    </div>
</div>
@endsection
