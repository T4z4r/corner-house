@extends('layouts.website.app')

@section('title', 'Stripe Checkout — Complete Your Payment')

@section('content')
@include('website._page-hero', ['kicker' => 'Step 3 of 3', 'title' => 'Complete Your Payment'])

<div class="container ch-section py-5">
    <!-- Checkout Progress Bar -->
    <div class="row justify-content-center mb-5">
        <div class="col-lg-10">
            <div class="d-flex align-items-center justify-content-between position-relative px-3 px-md-5">
                <div class="text-center position-relative z-1">
                    <div class="rounded-circle bg-success text-white d-inline-flex align-items-center justify-content-center shadow-sm" style="width: 40px; height: 40px; font-weight: 700;">
                        <i class="bi bi-check-lg fs-5"></i>
                    </div>
                    <div class="small fw-bold mt-2 text-dark">1. Select Stay</div>
                </div>
                <div class="flex-grow-1 mx-2" style="height: 3px; background: var(--ch-forest, #2d4d3a);"></div>
                <div class="text-center position-relative z-1">
                    <div class="rounded-circle bg-success text-white d-inline-flex align-items-center justify-content-center shadow-sm" style="width: 40px; height: 40px; font-weight: 700;">
                        <i class="bi bi-check-lg fs-5"></i>
                    </div>
                    <div class="small fw-bold mt-2 text-dark">2. Guest Details</div>
                </div>
                <div class="flex-grow-1 mx-2" style="height: 3px; background: var(--ch-forest, #2d4d3a);"></div>
                <div class="text-center position-relative z-1">
                    <div class="rounded-circle text-white d-inline-flex align-items-center justify-content-center shadow-lg" style="width: 40px; height: 40px; font-weight: 700; background: var(--ch-forest, #2d4d3a);">
                        3
                    </div>
                    <div class="small fw-bold mt-2 text-dark">3. Stripe Payment</div>
                </div>
            </div>
        </div>
    </div>

    @if (request()->query('cancelled'))
        <div class="alert alert-warning mb-4 rounded-3 shadow-sm d-flex align-items-center gap-3 p-3">
            <i class="bi bi-info-circle-fill fs-3 text-warning"></i>
            <div>
                <strong class="d-block">Payment was cancelled on Stripe</strong>
                <span>Your reservation reference <strong>{{ $reservation->reference }}</strong> is held. You can complete payment below.</span>
            </div>
        </div>
    @endif

    <div class="row g-5">
        <!-- Main Payment Column -->
        <div class="col-lg-7">
            <!-- Guest Summary Box -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0 text-dark" style="font-family:'Cormorant Garamond',serif; font-size:1.35rem;">
                            <i class="bi bi-person-check-fill text-success me-2"></i>Guest Contact Information
                        </h5>
                        <span class="badge text-bg-light border text-secondary px-2.5 py-1">Reference: {{ $reservation->reference }}</span>
                    </div>
                    <div class="row g-3 text-muted small">
                        <div class="col-sm-6">
                            <span class="d-block text-uppercase text-secondary" style="font-size:0.75rem; letter-spacing:0.05em;">Lead Guest</span>
                            <strong class="text-dark fs-6">{{ $reservation->guest?->first_name }} {{ $reservation->guest?->last_name }}</strong>
                        </div>
                        <div class="col-sm-6">
                            <span class="d-block text-uppercase text-secondary" style="font-size:0.75rem; letter-spacing:0.05em;">Email Address</span>
                            <strong class="text-dark fs-6">{{ $reservation->guest?->email }}</strong>
                        </div>
                        @if ($reservation->guest?->phone)
                            <div class="col-sm-6">
                                <span class="d-block text-uppercase text-secondary" style="font-size:0.75rem; letter-spacing:0.05em;">Phone Number</span>
                                <strong class="text-dark">{{ $reservation->guest->phone }}</strong>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Stripe Hosted Checkout Option -->
            @if ($checkoutUrl)
                <div class="card border-2 border-primary shadow-sm rounded-4 mb-4 bg-white overflow-hidden">
                    <div class="card-header bg-primary text-white p-3.5 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-credit-card-2-front-fill fs-4"></i>
                            <div>
                                <h6 class="mb-0 fw-bold">Instant Payment with Stripe Hosted Checkout</h6>
                                <span class="small text-white-50">Pay securely with Credit Card, Debit Card, Apple Pay or Google Pay</span>
                            </div>
                        </div>
                        <span class="badge bg-white text-primary fw-bold">Recommended</span>
                    </div>
                    <div class="card-body p-4 text-center">
                        <p class="text-muted small mb-3">You will be redirected to Stripe's encrypted payment gateway to insert payment details and confirm your stay.</p>
                        <a href="{{ $checkoutUrl }}" class="btn btn-primary btn-lg w-100 py-3 fw-bold shadow text-uppercase" style="letter-spacing:0.03em;">
                            <i class="bi bi-shield-lock-fill me-2"></i>Pay £{{ number_format($reservation->total_amount, 2) }} via Stripe Checkout
                        </a>
                        <div class="d-flex flex-wrap align-items-center justify-content-center gap-2 mt-3 text-muted small">
                            <span class="badge bg-light text-dark border px-2 py-1"><i class="bi bi-credit-card me-1"></i>Visa</span>
                            <span class="badge bg-light text-dark border px-2 py-1"><i class="bi bi-credit-card me-1"></i>Mastercard</span>
                            <span class="badge bg-light text-dark border px-2 py-1"><i class="bi bi-credit-card me-1"></i>American Express</span>
                            <span class="badge bg-light text-dark border px-2 py-1"><i class="bi bi-apple me-1"></i>Apple Pay</span>
                            <span class="badge bg-light text-dark border px-2 py-1"><i class="bi bi-google me-1"></i>Google Pay</span>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Direct Card Entry Form (Stripe Payment Element) -->
            @if ($paymentIntentSecret)
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-1 text-dark" style="font-family:'Cormorant Garamond',serif; font-size:1.35rem;">
                            <i class="bi bi-credit-card-fill text-dark me-2"></i>Direct Payment Entry
                        </h5>
                        <p class="text-muted small mb-4">Enter your card details below to authorize payment directly on this page.</p>

                        <form id="directCardForm" method="POST" action="{{ route('booking.checkout.confirm', $reservation) }}">
                            @csrf

                            <div id="payment-element" class="mb-3 p-3 border rounded-3 bg-white" style="min-height: 46px;"></div>
                            <div id="cardErrors" class="alert alert-danger d-none py-2 px-3 small mb-3" role="alert"></div>

                            <div class="form-check mb-4">
                                <input class="form-check-input" type="checkbox" id="termsCheck" required checked>
                                <label class="form-check-label small text-muted" for="termsCheck">
                                    I confirm the stay details and authorize the charge of <strong>£{{ number_format($reservation->total_amount, 2) }}</strong> via Stripe.
                                </label>
                            </div>

                            <button type="submit" class="btn btn-ch-book btn-lg w-100 py-3 shadow fw-bold" id="confirmPayBtn" disabled>
                                <i class="bi bi-shield-lock-fill me-2"></i>Authorize Payment of £{{ number_format($reservation->total_amount, 2) }}
                            </button>
                        </form>

                        <div class="d-flex align-items-center justify-content-center gap-3 mt-4 pt-2 border-top text-muted small">
                            <span><i class="bi bi-lock-fill text-success me-1"></i>256-Bit SSL Encrypted</span>
                            <span>·</span>
                            <span><i class="bi bi-shield-check me-1"></i>PCI DSS Level 1 Certified</span>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Sidebar Order Breakdown -->
        <div class="col-lg-5">
            <div class="ch-booking-card shadow-sm border-0 sticky-top" style="top: 2rem;">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h2 class="h4 font-serif text-dark mb-0">{{ $reservation->room?->name ?? 'Corner House Stay' }}</h2>
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Direct Discount</span>
                </div>
                <p class="ch-suite-meta mb-1"><i class="bi bi-calendar-event me-1"></i>{{ $reservation->check_in?->format('d M Y') }} → {{ $reservation->check_out?->format('d M Y') }}</p>

                @php
                    $nights = $reservation->check_in && $reservation->check_out ? $reservation->check_in->diffInDays($reservation->check_out) : 1;
                @endphp
                <p class="ch-suite-meta mb-3"><i class="bi bi-people me-1"></i>{{ $nights }} night(s) · {{ $reservation->guests_count }} guest(s)</p>

                <hr class="my-3">

                <!-- Itemized Cost Summary -->
                <h6 class="fw-bold text-dark mb-2" style="font-family:'Cormorant Garamond',serif; font-size:1.1rem;"><i class="bi bi-receipt me-1"></i>Itemized Price Breakdown</h6>

                <div class="d-flex justify-content-between py-1.5 small">
                    <span>Accommodation Stay ({{ $nights }} night{{ $nights > 1 ? 's' : '' }})</span>
                    <span>£{{ number_format((float) $reservation->base_amount, 2) }}</span>
                </div>

                @if ((float) $reservation->discount_amount > 0)
                    <div class="d-flex justify-content-between py-1.5 small text-success">
                        <span>Direct-booking discount ({{ \App\Models\Setting::getValue('direct_booking_discount', 10) }}%)</span>
                        <span class="fw-bold">-£{{ number_format((float) $reservation->discount_amount, 2) }}</span>
                    </div>
                @endif

                @if ((float) $reservation->fees_amount > 0)
                    <div class="d-flex justify-content-between py-1.5 small">
                        <span>Cleaning fee</span>
                        <span>£{{ number_format((float) $reservation->fees_amount, 2) }}</span>
                    </div>
                @endif

                @if ($reservation->relationLoaded('addons') && $reservation->addons->isNotEmpty())
                    @foreach ($reservation->addons as $addon)
                        <div class="d-flex justify-content-between py-1.5 small text-primary">
                            <span><i class="bi bi-plus-circle me-1"></i>{{ $addon->name }}</span>
                            <span>£{{ number_format((float) ($addon->pivot->total_price ?? $addon->price), 2) }}</span>
                        </div>
                    @endforeach
                @endif

                @if ((float) $reservation->damage_deposit > 0)
                    <div class="d-flex justify-content-between py-2 border-top border-bottom my-2 small bg-light p-2 rounded">
                        <span><i class="bi bi-info-circle text-muted me-1"></i>Damage deposit (refundable after stay)</span>
                        <span class="fw-bold text-dark">£{{ number_format((float) $reservation->damage_deposit, 2) }}</span>
                    </div>
                @endif

                @if ((float) $reservation->tax_amount > 0)
                    <div class="d-flex justify-content-between py-1.5 small text-muted">
                        <span>Taxes &amp; VAT</span>
                        <span>£{{ number_format((float) $reservation->tax_amount, 2) }}</span>
                    </div>
                @endif

                <hr class="my-3">

                <div class="d-flex justify-content-between align-items-center ch-price">
                    <div>
                        <span class="d-block fs-5 fw-bold text-dark">Total Amount Due</span>
                        <span class="text-muted small">Includes taxes &amp; deposit</span>
                    </div>
                    <span class="fs-2 fw-bold text-dark">£{{ number_format((float) $reservation->total_amount, 2) }}</span>
                </div>

                <div class="p-3 bg-light rounded-3 mt-4 border">
                    <div class="d-flex gap-2.5">
                        <i class="bi bi-patch-check-fill text-success fs-4"></i>
                        <div class="small">
                            <strong>Direct Booking Guarantee:</strong> You are receiving our lowest direct rate, with {{ \App\Models\Setting::getValue('direct_booking_discount', 10) }}% off third-party platform prices.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://js.stripe.com/v3/"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Add-on recalculation (unchanged).
    var checks = document.querySelectorAll('.addon-check');
    var baseTotal = parseFloat(document.getElementById('baseTotal').value);
    var summaryEl = document.getElementById('addonsSummary');
    var totalEl   = document.getElementById('totalDisplay');

    function recalc() {
        var addonTotal = 0, html = '';
        checks.forEach(function (cb) {
            if (cb.checked) {
                var price = parseFloat(cb.dataset.price);
                addonTotal += price;
                var name = cb.closest('label').querySelector('strong').textContent;
                html += '<div class="d-flex justify-content-between py-1 text-primary"><span>' + name + '</span><span>\u00a3' + price.toFixed(2) + '</span></div>';
            }
        });
        if (summaryEl) summaryEl.innerHTML = html;
        if (totalEl)   totalEl.textContent = '\u00a3' + (baseTotal + addonTotal).toFixed(2);
    }
    checks.forEach(function (cb) { cb.addEventListener('change', recalc); });

    // Stripe Payment Element.
    var stripeKey     = @json($stripeKey);
    var clientSecret  = @json($paymentIntentSecret);
    var returnUrl     = @json($paymentReturnUrl);
    var confirmUrl    = @json(route('booking.checkout.confirm', $reservation->id));
    var csrfToken     = document.querySelector('meta[name="csrf-token"]').content;
    var form          = document.getElementById('directCardForm');
    var btn           = document.getElementById('confirmPayBtn');
    var errorEl       = document.getElementById('cardErrors');

    if (!stripeKey || !clientSecret || !returnUrl || !form || !btn) return;

    function showError(msg) {
        if (errorEl) { errorEl.textContent = msg; errorEl.classList.remove('d-none'); }
    }
    function clearError() {
        if (errorEl) { errorEl.textContent = ''; errorEl.classList.add('d-none'); }
    }

    var stripe = Stripe(stripeKey);
    var elements = stripe.elements({ clientSecret: clientSecret });
    var paymentElement = elements.create('payment', { layout: 'tabs' });
    paymentElement.mount('#payment-element');

    paymentElement.on('change', function (event) {
        if (event.error) { showError(event.error.message); btn.disabled = true; }
        else { clearError(); btn.disabled = !event.complete; }
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var originalLabel = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Authorizing Payment\u2026';

        stripe.confirmPayment({
            elements: elements,
            confirmParams: { return_url: returnUrl },
            redirect: 'if_required'
        }).then(function (result) {
            if (result.error) {
                showError(result.error.message);
                btn.disabled = false;
                btn.innerHTML = originalLabel;
                return;
            }

            var paymentIntent = result.paymentIntent;
            if (!paymentIntent || paymentIntent.status !== 'succeeded') {
                btn.disabled = false;
                btn.innerHTML = originalLabel;
                return;
            }

            fetch(confirmUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ payment_intent_id: paymentIntent.id })
            }).then(function (resp) {
                if (resp.ok) { window.location.href = returnUrl; return null; }
                return resp.json();
            }).then(function (data) {
                if (data && data.error) { showError(data.error); btn.disabled = false; btn.innerHTML = originalLabel; }
            }).catch(function () { btn.disabled = false; btn.innerHTML = originalLabel; });
        });
    });
});
</script>
@endpush
@endsection
