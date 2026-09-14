@extends('layouts.website.app')

@section('title', 'Stripe Checkout — Complete Your Payment')

@section('content')
@include('website._page-hero', ['kicker' => 'Direct Booking · Step 3 of 3', 'title' => 'Complete Your Payment', 'subtitle' => 'Finalize your stay with instant 256-bit SSL encrypted Stripe payment processing.'])

<style>
/* Corner House Premium Checkout Tokens */
:root {
  --ch-ivy-deep: #1F3826;
  --ch-ivy: #2F5136;
  --ch-ivy-soft: #7E9A7A;
  --ch-sage: #D6DFCD;
  --ch-terracotta: #B4552B;
  --ch-terracotta-deep: #8C3E1C;
  --ch-terracotta-tint: #F0DED2;
  --ch-stone: #EEE8DB;
  --ch-stone-light: #F7F4EC;
  --ch-ink: #1E211C;
  --ch-ink-soft: #4F554B;
  --ch-line: rgba(31,56,38,.14);
}

.ch-checkout-container {
  padding-top: 2.5rem;
  padding-bottom: 5rem;
}

/* Stepper */
.ch-stepper {
  display: flex;
  align-items: center;
  justify-content: space-between;
  position: relative;
  max-width: 780px;
  margin: 0 auto 3rem auto;
}
.ch-stepper::before {
  content: "";
  position: absolute;
  top: 22px;
  left: 10%;
  right: 10%;
  height: 2px;
  background: var(--ch-ivy-deep);
  z-index: 0;
}
.ch-step-item {
  position: relative;
  z-index: 1;
  text-align: center;
  background: var(--ch-stone-light);
  padding: 0 0.8rem;
}
.ch-step-circle {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-family: "Fraunces", Georgia, serif;
  font-weight: 600;
  font-size: 1.1rem;
  border: 2px solid var(--ch-line);
  background: #fff;
  color: var(--ch-ink-soft);
}
.ch-step-item.completed .ch-step-circle {
  background: var(--ch-ivy-deep);
  border-color: var(--ch-ivy-deep);
  color: #fff;
}
.ch-step-item.active .ch-step-circle {
  background: var(--ch-terracotta);
  border-color: var(--ch-terracotta);
  color: #fff;
  box-shadow: 0 0 0 4px var(--ch-terracotta-tint);
}
.ch-step-label {
  display: block;
  font-size: 0.85rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  color: var(--ch-ink-soft);
  margin-top: 0.5rem;
  text-transform: uppercase;
}
.ch-step-item.active .ch-step-label {
  color: var(--ch-ivy-deep);
}

/* Form Card */
.ch-card-premium {
  background: #ffffff;
  border: 1px solid var(--ch-line);
  border-radius: 12px;
  padding: 2.2rem;
  box-shadow: 0 16px 40px -16px rgba(31,56,38,0.1);
}

.ch-card-header-title {
  font-family: "Fraunces", Georgia, serif;
  font-size: 1.5rem;
  color: var(--ch-ivy-deep);
  margin-bottom: 0.3rem;
  display: flex;
  align-items: center;
  gap: 0.6rem;
}

/* Payment Method Highlight Box */
.ch-payment-box {
  background: linear-gradient(135deg, rgba(31,56,38,0.04) 0%, rgba(180,85,43,0.04) 100%);
  border: 1.5px solid var(--ch-ivy-soft);
  border-radius: 10px;
  padding: 1.6rem;
}

/* Submit Button */
.btn-ch-pay {
  background: linear-gradient(145deg, var(--ch-terracotta), var(--ch-terracotta-deep));
  color: #ffffff;
  font-family: var(--body);
  font-weight: 700;
  font-size: 1.15rem;
  letter-spacing: 0.02em;
  padding: 1.15rem 1.8rem;
  border-radius: 8px;
  border: none;
  width: 100%;
  cursor: pointer;
  box-shadow: 0 10px 25px -5px rgba(180,85,43,0.4);
  transition: all 0.25s ease;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.6rem;
  text-decoration: none;
}
.btn-ch-pay:hover {
  background: linear-gradient(145deg, var(--ch-terracotta-deep), var(--ch-terracotta));
  transform: translateY(-2px);
  box-shadow: 0 14px 30px -4px rgba(180,85,43,0.5);
  color: #ffffff;
}

/* Order Summary Sidebar */
.ch-summary-card {
  background: #ffffff;
  border: 1px solid var(--ch-line);
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 16px 40px -16px rgba(31,56,38,0.12);
  position: sticky;
  top: 100px;
}
.ch-summary-media {
  height: 180px;
  position: relative;
  background: var(--ch-ivy-deep);
  overflow: hidden;
}
.ch-summary-media img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.ch-summary-media-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(to top, rgba(31,56,38,0.85) 0%, transparent 70%);
}
.ch-summary-media-title {
  position: absolute;
  bottom: 1rem;
  left: 1.2rem;
  right: 1.2rem;
  color: #ffffff;
  font-family: "Fraunces", Georgia, serif;
  font-size: 1.6rem;
  margin: 0;
}

.ch-summary-body {
  padding: 1.6rem;
}
.ch-date-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  background: var(--ch-ivy-deep);
  color: var(--ch-stone-light);
  padding: 0.45rem 0.9rem;
  border-radius: 99px;
  font-size: 0.85rem;
  font-weight: 600;
  margin-bottom: 1rem;
}
.ch-breakdown-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.45rem 0;
  font-size: 0.95rem;
  color: var(--ch-ink);
}
.ch-breakdown-row.discount {
  color: var(--ch-terracotta-deep);
  font-weight: 700;
}
.ch-breakdown-row.total-row {
  border-top: 2px solid var(--ch-ivy-deep);
  margin-top: 0.8rem;
  padding-top: 1rem;
}
.ch-total-price {
  font-family: "Fraunces", Georgia, serif;
  font-size: 2.2rem;
  color: var(--ch-ivy-deep);
  line-height: 1;
}

.ch-badge-guarantee {
  background: var(--ch-stone-light);
  border: 1px solid var(--ch-sage);
  border-radius: 8px;
  padding: 1rem;
  margin-top: 1.2rem;
  display: flex;
  gap: 0.8rem;
  align-items: flex-start;
}
.ch-badge-guarantee i {
  color: var(--ch-terracotta);
  font-size: 1.3rem;
  flex-shrink: 0;
}
</style>

<div class="wrap ch-checkout-container">

    <!-- 3-Step Progress Bar -->
    <div class="ch-stepper">
        <div class="ch-step-item completed">
            <div class="ch-step-circle"><i class="bi bi-check-lg"></i></div>
            <span class="ch-step-label">1. Dates</span>
        </div>
        <div class="ch-step-item completed">
            <div class="ch-step-circle"><i class="bi bi-check-lg"></i></div>
            <span class="ch-step-label">2. Details</span>
        </div>
        <div class="ch-step-item active">
            <div class="ch-step-circle">3</div>
            <span class="ch-step-label">3. Payment</span>
        </div>
    </div>

    @if (request()->query('cancelled'))
        <div class="alert alert-warning mb-4 rounded-3 shadow-sm border-0 d-flex align-items-center gap-3 p-3" style="background:#fff9e6; color:#664d03; border-left:4px solid #c9a227 !important;">
            <i class="bi bi-info-circle-fill fs-3" style="color:#c9a227;"></i>
            <div>
                <strong class="d-block">Payment was cancelled on Stripe</strong>
                <span>Your reservation reference <strong>{{ $reservation->reference }}</strong> is preserved. You can complete payment below.</span>
            </div>
        </div>
    @endif

    <div class="row g-5">
        <!-- Main Payment Column -->
        <div class="col-lg-7">

            <!-- Guest Contact Summary -->
            <div class="ch-card-premium mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom" style="border-color:var(--ch-line) !important;">
                    <h2 class="ch-card-header-title" style="font-size:1.35rem; margin:0;">
                        <i class="bi bi-person-check-fill" style="color:var(--ch-ivy-deep);"></i>
                        Lead Guest Contact Information
                    </h2>
                    <span class="badge" style="background:var(--ch-stone-light); color:var(--ch-ivy-deep); border:1px solid var(--ch-sage); font-weight:600; font-size:0.8rem;">Ref: {{ $reservation->reference }}</span>
                </div>
                <div class="row g-3 text-muted small">
                    <div class="col-sm-6">
                        <span class="d-block text-uppercase fw-bold" style="font-size:0.72rem; letter-spacing:0.08em; color:var(--ch-ivy-deep);">Lead Guest</span>
                        <strong class="text-dark fs-6">{{ $reservation->guest?->first_name }} {{ $reservation->guest?->last_name }}</strong>
                    </div>
                    <div class="col-sm-6">
                        <span class="d-block text-uppercase fw-bold" style="font-size:0.72rem; letter-spacing:0.08em; color:var(--ch-ivy-deep);">Email Address</span>
                        <strong class="text-dark fs-6">{{ $reservation->guest?->email }}</strong>
                    </div>
                    @if ($reservation->guest?->phone)
                        <div class="col-sm-6">
                            <span class="d-block text-uppercase fw-bold" style="font-size:0.72rem; letter-spacing:0.08em; color:var(--ch-ivy-deep);">Phone Number</span>
                            <strong class="text-dark">{{ $reservation->guest->phone }}</strong>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Stripe Hosted Checkout Option -->
            @if ($checkoutUrl)
                <div class="ch-card-premium mb-4" style="border-color:var(--ch-ivy); background:linear-gradient(180deg, #ffffff 0%, var(--ch-stone-light) 100%);">
                    <div class="d-flex align-items-center justify-content-between mb-3 pb-3 border-bottom" style="border-color:var(--ch-line) !important;">
                        <div class="d-flex align-items-center gap-3">
                            <i class="bi bi-shield-lock-fill fs-2" style="color:var(--ch-terracotta);"></i>
                            <div>
                                <h3 style="font-family:'Fraunces',serif; font-size:1.35rem; color:var(--ch-ivy-deep); margin:0;">Stripe Instant Checkout</h3>
                                <span class="small text-muted">256-Bit Encrypted Payment Gateway</span>
                            </div>
                        </div>
                        <span class="badge" style="background:var(--ch-terracotta); color:#fff; font-size:0.75rem; letter-spacing:0.06em; text-transform:uppercase; padding:0.35rem 0.7rem;">Recommended</span>
                    </div>

                    <p class="small text-muted mb-4">Click below to proceed to Stripe's secure payment checkout to complete your reservation for <strong>£{{ number_format($reservation->total_amount, 2) }}</strong>.</p>

                    <a href="{{ $checkoutUrl }}" class="btn-ch-pay">
                        <i class="bi bi-shield-lock-fill me-1"></i>
                        <span>Pay £{{ number_format($reservation->total_amount, 2) }} via Stripe Checkout</span>
                        <i class="bi bi-arrow-right ms-1"></i>
                    </a>

                    <div class="d-flex flex-wrap align-items-center justify-content-center gap-2 mt-4 pt-3 border-top" style="border-color:var(--ch-line) !important;">
                        <span class="badge bg-white text-dark border px-2.5 py-1.5 small"><i class="bi bi-credit-card me-1"></i>Visa</span>
                        <span class="badge bg-white text-dark border px-2.5 py-1.5 small"><i class="bi bi-credit-card me-1"></i>Mastercard</span>
                        <span class="badge bg-white text-dark border px-2.5 py-1.5 small"><i class="bi bi-credit-card me-1"></i>American Express</span>
                        <span class="badge bg-white text-dark border px-2.5 py-1.5 small"><i class="bi bi-apple me-1"></i>Apple Pay</span>
                        <span class="badge bg-white text-dark border px-2.5 py-1.5 small"><i class="bi bi-google me-1"></i>Google Pay</span>
                    </div>
                </div>
            @endif

            <!-- Direct Payment Form -->
            <div class="ch-card-premium">
                <h3 class="ch-card-header-title" style="font-size:1.35rem;">
                    <i class="bi bi-credit-card-fill" style="color:var(--ch-ivy-deep);"></i>
                    Direct Card Payment Entry
                </h3>
                <p class="small text-muted mb-4">Authorize payment directly on this page via Stripe.</p>

                <form method="POST" action="{{ route('booking.checkout.confirm', $reservation) }}" id="directCardForm">
                    @csrf

                    <div class="mb-3">
                        <label class="ch-form-label">Cardholder Name</label>
                        <input type="text" name="cardholder_name" class="ch-form-control" value="{{ $reservation->guest?->first_name }} {{ $reservation->guest?->last_name }}" required placeholder="Name on card">
                    </div>

                    <div class="mb-3">
                        <label class="ch-form-label">Card Number</label>
                        <input type="text" name="card_number" class="ch-form-control" id="cardNumberInput" placeholder="4242 4242 4242 4242" required maxlength="19">
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-6">
                            <label class="ch-form-label">Expiry Date</label>
                            <input type="text" name="card_expiry" class="ch-form-control" id="cardExpiryInput" placeholder="MM / YY" required maxlength="7">
                        </div>
                        <div class="col-6">
                            <label class="ch-form-label">CVC / CVV</label>
                            <input type="password" name="card_cvc" class="ch-form-control" placeholder="CVC" required maxlength="4">
                        </div>
                    </div>

                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" id="termsCheck" required checked>
                        <label class="form-check-label small text-muted" for="termsCheck">
                            I confirm the stay details and authorize the charge of <strong>£{{ number_format($reservation->total_amount, 2) }}</strong> via Stripe.
                        </label>
                    </div>

                    <button type="submit" class="btn-ch-pay" id="confirmPayBtn">
                        <i class="bi bi-shield-lock-fill"></i>
                        <span>Confirm Payment of £{{ number_format($reservation->total_amount, 2) }}</span>
                    </button>
                </form>

                <div class="d-flex align-items-center justify-content-center gap-3 mt-4 pt-3 border-top text-muted small" style="border-color:var(--ch-line) !important;">
                    <span><i class="bi bi-lock-fill me-1" style="color:var(--ch-ivy-deep);"></i>256-Bit SSL Encryption</span>
                    <span>·</span>
                    <span><i class="bi bi-shield-check me-1" style="color:var(--ch-ivy-deep);"></i>PCI-DSS Compliant</span>
                </div>
            </div>
        </div>

        <!-- Sidebar Order Breakdown -->
        <div class="col-lg-5">
            <div class="ch-summary-card">
                <!-- Media Header -->
                <div class="ch-summary-media">
                    @php $roomHero = $reservation->room?->images->first(); @endphp
                    @if ($roomHero)
                        <img src="{{ asset('storage/'.$roomHero->path) }}" alt="{{ $reservation->room?->name }}">
                    @else
                        <div class="d-flex align-items-center justify-content-center h-100 text-white fs-1"><i class="bi bi-house-heart"></i></div>
                    @endif
                    <div class="ch-summary-media-overlay"></div>
                    <h3 class="ch-summary-media-title">{{ $reservation->room?->name ?? 'Corner House Stay' }}</h3>
                </div>

                <div class="ch-summary-body">
                    <!-- Stay Pill -->
                    <div class="ch-date-pill">
                        <i class="bi bi-calendar-check"></i>
                        <span>{{ $reservation->check_in?->format('d M Y') }} → {{ $reservation->check_out?->format('d M Y') }}</span>
                    </div>

                    @php
                        $nights = $reservation->check_in && $reservation->check_out ? $reservation->check_in->diffInDays($reservation->check_out) : 1;
                    @endphp
                    <div class="small text-muted mb-3"><i class="bi bi-moon-stars me-1"></i>{{ $nights }} night(s) · {{ $reservation->guests_count }} guest(s)</div>

                    <!-- Itemized Cost Summary -->
                    <div class="ch-breakdown-row">
                        <span>Accommodation Stay ({{ $nights }} night{{ $nights > 1 ? 's' : '' }})</span>
                        <span>£{{ number_format((float) $reservation->base_amount, 2) }}</span>
                    </div>

                    @if ((float) $reservation->discount_amount > 0)
                        <div class="ch-breakdown-row discount">
                            <span>Direct Discount ({{ \App\Models\Setting::getValue('direct_booking_discount', 10) }}%)</span>
                            <span>-£{{ number_format((float) $reservation->discount_amount, 2) }}</span>
                        </div>
                    @endif

                    @if ((float) $reservation->fees_amount > 0)
                        <div class="ch-breakdown-row">
                            <span>Cleaning Fee</span>
                            <span>£{{ number_format((float) $reservation->fees_amount, 2) }}</span>
                        </div>
                    @endif

                    @if ($reservation->relationLoaded('addons') && $reservation->addons->isNotEmpty())
                        @foreach ($reservation->addons as $addon)
                            <div class="ch-breakdown-row text-primary">
                                <span>+ {{ $addon->name }}</span>
                                <span>£{{ number_format((float) ($addon->pivot->total_price ?? $addon->price), 2) }}</span>
                            </div>
                        @endforeach
                    @endif

                    @if ((float) $reservation->damage_deposit > 0)
                        <div class="ch-breakdown-row py-2 my-2 border-top border-bottom small text-muted">
                            <span><i class="bi bi-info-circle me-1"></i>Refundable Security Deposit</span>
                            <span class="fw-bold text-dark">£{{ number_format((float) $reservation->damage_deposit, 2) }}</span>
                        </div>
                    @endif

                    @if ((float) $reservation->tax_amount > 0)
                        <div class="ch-breakdown-row text-muted small">
                            <span>Taxes &amp; VAT</span>
                            <span>£{{ number_format((float) $reservation->tax_amount, 2) }}</span>
                        </div>
                    @endif

                    <!-- Grand Total -->
                    <div class="ch-breakdown-row total-row">
                        <div>
                            <span class="d-block fw-bold text-dark fs-6" style="font-family:'Fraunces',serif;">Grand Total</span>
                            <span class="small text-muted">Includes stay, deposit &amp; tax</span>
                        </div>
                        <span class="ch-total-price">£{{ number_format((float) $reservation->total_amount, 2) }}</span>
                    </div>

                    <!-- Direct Guarantee Banner -->
                    <div class="ch-badge-guarantee">
                        <i class="bi bi-patch-check-fill"></i>
                        <div class="small">
                            <strong class="d-block text-dark mb-0.5">Lowest Direct Rate</strong>
                            <span>You are saving {{ \App\Models\Setting::getValue('direct_booking_discount', 10) }}% off platform prices by booking direct with Corner House.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const cardInput = document.getElementById('cardNumberInput');
    const expiryInput = document.getElementById('cardExpiryInput');
    const form = document.getElementById('directCardForm');
    const btn = document.getElementById('confirmPayBtn');

    if (cardInput) {
        cardInput.addEventListener('input', function (e) {
            let val = e.target.value.replace(/\D/g, '');
            val = val.replace(/(.{4})/g, '$1 ').trim();
            e.target.value = val.substring(0, 19);
        });
    }

    if (expiryInput) {
        expiryInput.addEventListener('input', function (e) {
            let val = e.target.value.replace(/\D/g, '');
            if (val.length >= 2) {
                val = val.substring(0, 2) + ' / ' + val.substring(2, 4);
            }
            e.target.value = val.substring(0, 7);
        });
    }

    if (form && btn) {
        form.addEventListener('submit', function () {
            btn.disabled = true;
            btn.style.opacity = '0.8';
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing Payment...';
        });
    }
});
</script>
@endpush
@endsection

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
