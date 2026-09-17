@extends('layouts.website.app')
@section('title', 'Security Deposit Hold')
@section('robots', 'noindex, nofollow')
@section('content')
<style>
.deposit-card { background:#fff; border:1px solid rgba(31,56,38,.15); border-radius:16px; padding:clamp(1.25rem,4vw,2.5rem); box-shadow:0 16px 40px rgba(31,56,38,.08); }
.deposit-card h2, .deposit-card h3 { color:#1f3826; font-family:"Fraunces",Georgia,serif; }
.deposit-card p { margin:1rem 0; line-height:1.7; }
.deposit-card .text-danger { color:#b42318; }
.deposit-consent { display:flex; gap:.75rem; align-items:flex-start; margin:1.25rem 0; }
.deposit-consent input { margin-top:.3rem; flex-shrink:0; }
#deposit-submit { width:100%; }
#deposit-submit:disabled { opacity:.55; cursor:wait; }
</style>
<section class="ch-page-hero">
    <div class="wrap">
        <p class="ch-kicker">{{ $payment->reservation->reference }}</p>
        <h1>Refundable security deposit</h1>
        <p>A temporary card hold for your stay at Corner House.</p>
    </div>
</section>
<div class="wrap" style="max-width:720px;padding-top:2rem;padding-bottom:4rem;">
    <div class="deposit-card">
        <h2>{{ $payment->currency }} {{ number_format($payment->amount, 2) }}</h2>
        @if ($error)
            <p class="text-danger" role="alert">{{ $error }}</p>
        @elseif ($payment->status === 'processing')
            <h3>Security deposit authorised</h3>
            <p>Your funds are held, not charged. We will release the hold after your stay, subject to the rental agreement. Your bank controls when the funds become available again.</p>
            @if ($payment->metadata['capture_before'] ?? null)
                <p>This authorisation expires {{ \Illuminate\Support\Carbon::createFromTimestamp($payment->metadata['capture_before'])->setTimezone('Europe/London')->format('d M Y H:i T') }}. We will contact you if a new authorisation is needed.</p>
            @endif
        @elseif ($payment->status === 'cancelled')
            <h3>Hold released or expired</h3>
            <p>No funds remain held by this authorisation. Your bank may take time to update your available balance.</p>
        @elseif (in_array($payment->status, ['paid', 'refunded'], true))
            <p>Security deposit status: {{ $payment->statusLabel() }}. Please contact us if you have any questions.</p>
        @else
            <p>This is separate from your booking payment. We will reserve this amount on your card without charging it. The hold will be released after your stay, subject to the rental agreement.</p>
            <p>Card holds normally expire within seven days. A longer stay may require a new authorisation.</p>
            <form id="deposit-form" data-skip-loading-state>
                <div id="deposit-payment-element" class="mb-3"></div>
                <p id="deposit-error" class="text-danger" role="alert" hidden></p>
                <label class="deposit-consent">
                    <input id="deposit-consent" type="checkbox" required>
                    <span>I authorise a temporary security deposit hold of {{ $payment->currency }} {{ number_format($payment->amount, 2) }} under the rental agreement.</span>
                </label>
                <button id="deposit-submit" class="btn btn-primary w-100" type="submit" disabled>Authorise security deposit hold</button>
            </form>
        @endif
    </div>
</div>
@endsection
@push('scripts')
@if (! $error && $payment->status === 'pending')
<script src="https://js.stripe.com/v3/"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const button = document.getElementById('deposit-submit');
    const errorBox = document.getElementById('deposit-error');
    const key = @json($stripeKey);
    const secret = @json($payment->metadata['client_secret'] ?? null);
    const returnUrl = @json($returnUrl);
    if (!window.Stripe || !key || !secret) {
        errorBox.textContent = 'Secure payments could not load. Please refresh the page or contact us.';
        errorBox.hidden = false;
        return;
    }
    const stripe = Stripe(key);
    const elements = stripe.elements({clientSecret: secret});
    const paymentElement = elements.create('payment');
    let complete = false;
    let processing = false;
    paymentElement.on('change', function (event) {
        complete = event.complete;
        button.disabled = processing || !complete;
    });
    paymentElement.mount('#deposit-payment-element');
    document.getElementById('deposit-form').addEventListener('submit', async function (event) {
        event.preventDefault();
        if (processing || !complete || !document.getElementById('deposit-consent').checked) return;
        processing = true;
        button.disabled = true;
        errorBox.hidden = true;
        try {
            const submitted = await elements.submit();
            if (submitted.error) throw submitted.error;
            const result = await stripe.confirmPayment({elements, confirmParams: {return_url: returnUrl}, redirect: 'if_required'});
            if (result.error) throw result.error;
            if (result.paymentIntent?.status !== 'requires_capture') {
                throw new Error('Your authorisation is not complete yet. Please refresh to check its status.');
            }
            window.location.assign(returnUrl);
        } catch (error) {
            errorBox.textContent = error.message || 'Unable to authorise the hold. Please try again.';
            errorBox.hidden = false;
            processing = false;
            button.disabled = !complete;
        }
    });
});
</script>
@endif
@endpush
