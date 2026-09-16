@extends('layouts.website.app')

@section('title', 'Complete Your Payment')
@section('robots', 'noindex, nofollow')

@section('content')

@php($guest = $reservation->guest?->exists ? $reservation->guest : null)
@php($fullName = trim(($guest?->first_name ?? '').' '.($guest?->last_name ?? '')))
@php($guestEmail = $guest?->email ?? '')
@php($nights = $reservation->check_in && $reservation->check_out ? $reservation->check_in->diffInDays($reservation->check_out) : 1)
@php($balanceDueNote = $balanceDue > 0 ? ' &mdash; the balance of &pound;'.number_format($balanceDue, 2).' is due before arrival' : '')
@php($balanceDueSentence = $balanceDue > 0 ? ' The balance of &pound;'.number_format($balanceDue, 2).' is due before arrival.' : '')

<section class="ch-page-hero">
    <div class="wrap">
        <p class="ch-kicker">Direct Booking &middot; Step 3 of 3</p>
        <h1>Complete Your Payment</h1>
        <p class="ch-page-hero-sub">Pay your refundable &pound;{{ number_format($deposit, 0) }} deposit to secure the booking. The balance is due before arrival.</p>
    </div>
</section>

<style>
/* ===== Corner House Checkout Design System ===== */
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
  --ch-serif: "Fraunces", Georgia, serif;
}

.ch-checkout-container {
  padding-top: 2.4rem;
  padding-bottom: 5rem;
}

/* ---- Stepper ---- */
.ch-stepper {
  display: flex;
  align-items: center;
  justify-content: space-between;
  position: relative;
  max-width: 720px;
  margin: 0 auto 2.8rem auto;
}
.ch-stepper::before {
  content: "";
  position: absolute;
  top: 22px;
  left: 12%;
  right: 12%;
  height: 2px;
  background: var(--ch-line);
  z-index: 0;
}
.ch-step-item {
  position: relative;
  z-index: 1;
  text-align: center;
  background: var(--ch-stone-light);
  padding: 0 .9rem;
}
.ch-step-circle {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-family: var(--ch-serif);
  font-weight: 600;
  font-size: 1.1rem;
  border: 2px solid var(--ch-line);
  background: #fff;
  color: var(--ch-ink-soft);
}
.ch-step-circle svg { width: 18px; height: 18px; }
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
  font-size: .82rem;
  font-weight: 700;
  letter-spacing: .05em;
  color: var(--ch-ink-soft);
  margin-top: .5rem;
  text-transform: uppercase;
}
.ch-step-item.active .ch-step-label { color: var(--ch-ivy-deep); }

/* ---- Layout grid ---- */
.ch-checkout-grid {
  display: grid;
  grid-template-columns: minmax(0,1.5fr) minmax(320px,.9fr);
  gap: 2.4rem;
  align-items: start;
}
@media (max-width: 920px) { .ch-checkout-grid { grid-template-columns: 1fr; } }

/* ---- Cards ---- */
.ch-card-premium {
  background: #fff;
  border: 1px solid var(--ch-line);
  border-radius: 14px;
  padding: 1.8rem 1.9rem;
  box-shadow: 0 18px 44px -22px rgba(31,56,38,.16);
}
.ch-card-premium + .ch-card-premium { margin-top: 1.4rem; }
.ch-card-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  flex-wrap: wrap;
  padding-bottom: 1.1rem;
  margin-bottom: 1.3rem;
  border-bottom: 1px solid var(--ch-line);
}
.ch-card-header-title {
  font-family: var(--ch-serif);
  font-size: 1.32rem;
  color: var(--ch-ivy-deep);
  margin: 0;
  display: flex;
  align-items: center;
  gap: .7rem;
  line-height: 1.15;
}
.ch-step-badge {
  background: var(--ch-ivy-deep);
  color: var(--ch-stone-light);
  width: 28px;
  height: 28px;
  border-radius: 50%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: .85rem;
  font-weight: 700;
  flex: none;
}
.ch-ref-pill {
  border: 1px solid var(--ch-sage);
  background: var(--ch-stone-light);
  color: var(--ch-ivy-deep);
  font-weight: 700;
  font-size: .78rem;
  letter-spacing: .04em;
  padding: .3rem .7rem;
  border-radius: 99px;
}

/* ---- Contact summary ---- */
.ch-contact-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.1rem 1.8rem;
}
@media (max-width: 560px) { .ch-contact-grid { grid-template-columns: 1fr; } }
.ch-contact-cell .ch-contact-label {
  display: block;
  font-size: .68rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .09em;
  color: var(--ch-ivy-deep);
  margin-bottom: .15rem;
}
.ch-contact-cell strong { color: var(--ch-ink); font-size: .98rem; font-weight: 600; }

/* ---- Form controls ---- */
.ch-form-group { margin-bottom: 1.35rem; }
.ch-form-label {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  font-size: .74rem;
  font-weight: 700;
  letter-spacing: .09em;
  text-transform: uppercase;
  color: var(--ch-ivy-deep);
  margin-bottom: .45rem;
}
.ch-form-control {
  width: 100%;
  padding: .88rem 1rem;
  font-family: var(--body);
  font-size: .98rem;
  color: var(--ch-ink);
  background: var(--ch-stone-light);
  border: 1px solid rgba(31,56,38,.22);
  border-radius: 8px;
  transition: border-color .18s ease, box-shadow .18s ease, background .18s ease;
}
.ch-form-control::placeholder { color: rgba(31,56,38,.38); }
.ch-form-control:focus {
  outline: none;
  background: #fff;
  border-color: var(--ch-ivy-deep);
  box-shadow: 0 0 0 3px rgba(31,56,38,.12);
}
.ch-field-help { font-size: .8rem; color: var(--ch-ink-soft); margin-top: .5rem; }

/* ---- Stripe Payment Element mount ---- */
.ch-stripe-element {
  background: var(--ch-stone-light);
  border: 1px solid rgba(31,56,38,.22);
  border-radius: 8px;
  padding: .9rem 1rem;
  min-height: 62px;
  transition: border-color .18s ease, box-shadow .18s ease, background .18s ease;
}
.ch-stripe-element:focus-within {
  background: #fff;
  border-color: var(--ch-ivy-deep);
  box-shadow: 0 0 0 3px rgba(31,56,38,.12);
}

/* ---- Authorization checkbox ---- */
.ch-check {
  display: flex;
  align-items: flex-start;
  gap: .7rem;
  cursor: pointer;
  margin: 1.35rem 0 1.2rem;
  user-select: none;
}
.ch-check input[type="checkbox"] {
  appearance: none;
  -webkit-appearance: none;
  width: 20px;
  height: 20px;
  flex: none;
  margin: 2px 0 0;
  border: 2px solid var(--ch-ivy-soft);
  border-radius: 5px;
  background: #fff;
  display: grid;
  place-items: center;
  cursor: pointer;
  transition: background .15s ease, border-color .15s ease;
}
.ch-check input[type="checkbox"]::after {
  content: "";
  width: 9px;
  height: 5px;
  border-left: 2px solid #fff;
  border-bottom: 2px solid #fff;
  transform: rotate(-45deg) translate(0, -1px);
  opacity: 0;
  transition: opacity .12s ease;
}
.ch-check input[type="checkbox"]:checked { background: var(--ch-ivy-deep); border-color: var(--ch-ivy-deep); }
.ch-check input[type="checkbox"]:checked::after { opacity: 1; }
.ch-check-text { font-size: .88rem; color: var(--ch-ink-soft); line-height: 1.45; }
.ch-check-text strong { color: var(--ch-ivy-deep); }

/* ---- Alerts ---- */
.ch-alert {
  display: flex;
  gap: .8rem;
  align-items: flex-start;
  padding: 1rem 1.15rem;
  border-radius: 10px;
  margin-bottom: 1.5rem;
  font-size: .92rem;
  line-height: 1.5;
}
.ch-alert svg { width: 20px; height: 20px; flex: none; margin-top: 2px; }
.ch-alert-strong { display: block; font-weight: 700; margin-bottom: .15rem; }
.ch-alert-warning { background: #FFF9E9; color: #664D03; border-left: 4px solid #C9A227; }
.ch-alert-warning svg { color: #C9A227; }
.ch-form-error {
  display: flex;
  gap: .6rem;
  align-items: flex-start;
  background: #FDEFEC;
  color: #842029;
  border-left: 4px solid var(--ch-terracotta);
  border-radius: 8px;
  padding: .8rem 1rem;
  font-size: .9rem;
  margin-bottom: 1rem;
}
.ch-form-error[hidden] { display: none; }
.ch-form-error svg { width: 18px; height: 18px; flex: none; margin-top: 1px; }

/* ---- Buttons ---- */
.btn-ch-pay {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: .6rem;
  width: 100%;
  background: linear-gradient(145deg, var(--ch-terracotta), var(--ch-terracotta-deep));
  color: #fff;
  font-family: var(--body);
  font-weight: 700;
  font-size: 1.06rem;
  letter-spacing: .02em;
  padding: 1.1rem 1.6rem;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  text-decoration: none;
  box-shadow: 0 10px 26px -6px rgba(180,85,43,.45);
  transition: transform .2s ease, box-shadow .2s ease, filter .2s ease;
}
.btn-ch-pay svg { width: 20px; height: 20px; flex: none; }
.btn-ch-pay:hover { background: linear-gradient(145deg, var(--ch-terracotta-deep), var(--ch-terracotta)); transform: translateY(-2px); box-shadow: 0 14px 32px -6px rgba(180,85,43,.55); color: #fff; }
.btn-ch-pay:disabled { cursor: not-allowed; filter: saturate(.6) brightness(.96); transform: none; }
.ch-pay-trust {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: center;
  gap: .6rem 1.1rem;
  margin-top: 1.1rem;
  color: var(--ch-ink-soft);
  font-size: .78rem;
}
.ch-pay-trust span { display: inline-flex; align-items: center; gap: .35rem; }
.ch-pay-trust svg { width: 14px; height: 14px; color: var(--ch-ivy-deep); }

/* ---- Payment method chips ---- */
.ch-chip-row {
  display: flex;
  flex-wrap: wrap;
  gap: .45rem;
  padding-top: 1rem;
  margin-top: 1rem;
  border-top: 1px solid var(--ch-line);
}
.ch-chip {
  background: #fff;
  border: 1px solid var(--ch-line);
  color: var(--ch-ivy-deep);
  font-weight: 700;
  font-size: .74rem;
  padding: .22rem .65rem;
  border-radius: 99px;
  display: inline-flex;
  align-items: center;
  gap: .35rem;
}
.ch-chip svg { width: 14px; height: 14px; }
.ch-chip-recommended {
  background: var(--ch-terracotta);
  border-color: var(--ch-terracotta);
  color: #fff;
  letter-spacing: .06em;
  text-transform: uppercase;
  font-size: .7rem;
  padding: .32rem .75rem;
}

/* ---- Hosted card variant ---- */
.ch-card-hosted {
  border-color: var(--ch-ivy);
  background: linear-gradient(180deg, #fff 0%, var(--ch-stone-light) 100%);
}
.ch-hosted-head { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; }
.ch-hosted-title { display: flex; align-items: center; gap: .8rem; }
.ch-hosted-title svg { width: 30px; height: 30px; color: var(--ch-terracotta); flex: none; }
.ch-hosted-lead { font-size: .9rem; color: var(--ch-ink-soft); margin: 1rem 0 1.2rem; }
.ch-hosted-lead strong { color: var(--ch-ivy-deep); }

/* ---- Order summary sidebar ---- */
.ch-summary-card {
  background: #fff;
  border: 1px solid var(--ch-line);
  border-radius: 14px;
  overflow: hidden;
  box-shadow: 0 18px 44px -22px rgba(31,56,38,.18);
  position: sticky;
  top: 104px;
}
.ch-summary-media { height: 180px; position: relative; background: var(--ch-ivy-deep); overflow: hidden; }
.ch-summary-media img { width: 100%; height: 100%; object-fit: cover; }
.ch-summary-media-overlay { position: absolute; inset: 0; background: linear-gradient(to top, rgba(31,56,38,.85) 0%, transparent 70%); }
.ch-summary-media-title {
  position: absolute;
  bottom: 1rem;
  left: 1.25rem;
  right: 1.25rem;
  color: #fff;
  font-family: var(--ch-serif);
  font-size: 1.55rem;
  margin: 0;
}
.ch-summary-body { padding: 1.5rem 1.6rem; }
.ch-date-pill {
  display: inline-flex;
  align-items: center;
  gap: .5rem;
  background: var(--ch-ivy-deep);
  color: var(--ch-stone-light);
  padding: .45rem .9rem;
  border-radius: 99px;
  font-size: .84rem;
  font-weight: 600;
  margin-bottom: .9rem;
}
.ch-date-pill svg { width: 16px; height: 16px; }
.ch-stay-note { font-size: .88rem; color: var(--ch-ink-soft); margin-bottom: .9rem; }
.ch-breakdown-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: .42rem 0;
  font-size: .93rem;
  color: var(--ch-ink);
}
.ch-breakdown-row.discount { color: var(--ch-terracotta-deep); font-weight: 700; }
.ch-breakdown-row.deposit { font-size: .85rem; color: var(--ch-ink-soft); }
.ch-breakdown-row.total-row { border-top: 2px solid var(--ch-ivy-deep); margin-top: .8rem; padding-top: 1rem; }
.ch-summary-sub { font-size: .78rem; color: var(--ch-ink-soft); }
.ch-total-price {
  font-family: var(--ch-serif);
  font-size: 2.1rem;
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
  gap: .8rem;
  align-items: flex-start;
}
.ch-badge-guarantee svg { color: var(--ch-terracotta); width: 20px; height: 20px; flex: none; margin-top: 2px; }
.ch-badge-guarantee .small { color: var(--ch-ink-soft); }
.ch-badge-guarantee strong { color: var(--ch-ink); }

/* Shared icon + generic helpers */
.ch-icon { width: 1em; height: 1em; flex: none; }
@media (max-width: 560px) { .ch-card-premium { padding: 1.4rem 1.15rem; } }
</style>

<div class="wrap ch-checkout-container">

    <!-- Stepper -->
    <div class="ch-stepper">
        <div class="ch-step-item completed">
            <div class="ch-step-circle">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 13l4 4 10-10"/></svg>
            </div>
            <span class="ch-step-label">1. Dates</span>
        </div>
        <div class="ch-step-item completed">
            <div class="ch-step-circle">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 13l4 4 10-10"/></svg>
            </div>
            <span class="ch-step-label">2. Details</span>
        </div>
        <div class="ch-step-item active">
            <div class="ch-step-circle">3</div>
            <span class="ch-step-label">3. Payment</span>
        </div>
    </div>

    @if (request()->query('cancelled'))
        <div class="ch-alert ch-alert-warning" role="status">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
            <div>
                <span class="ch-alert-strong">Payment was cancelled on Stripe</span>
                <span>Your reservation reference <strong>{{ $reservation->reference }}</strong> is preserved. You can complete payment below whenever you are ready.</span>
            </div>
        </div>
    @endif

    <div class="ch-checkout-grid">

        <!-- Main column -->
        <div class="ch-checkout-main">

            <!-- Guest contact -->
            <div class="ch-card-premium">
                <div class="ch-card-head">
                    <h2 class="ch-card-header-title">
                        <span class="ch-step-badge">1</span>
                        Lead Guest Contact Information
                    </h2>
                    <span class="ch-ref-pill">Ref: {{ $reservation->reference }}</span>
                </div>
                <div class="ch-contact-grid">
                    <div class="ch-contact-cell">
                        <span class="ch-contact-label">Lead Guest</span>
                        <strong>{{ $fullName ?: '—' }}</strong>
                    </div>
                    <div class="ch-contact-cell">
                        <span class="ch-contact-label">Email Address</span>
                        <strong>{{ $guestEmail }}</strong>
                    </div>
                    @if ($guest?->phone)
                        <div class="ch-contact-cell">
                            <span class="ch-contact-label">Phone Number</span>
                            <strong>{{ $guest->phone }}</strong>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Hosted Stripe Checkout option -->
            @if ($checkoutUrl)
                <div class="ch-card-premium ch-card-hosted">
                    <div class="ch-hosted-head">
                        <div class="ch-hosted-title">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M12 2l7 3v6c0 5-3 8.5-7 10-4-1.5-7-5-7-10V5z"/><path d="M9 12l2 2 4-4"/>
                            </svg>
                            <div>
                                <h3 style="font-family:var(--ch-serif); font-size:1.28rem; color:var(--ch-ivy-deep); margin:0;">Stripe Instant Checkout</h3>
                                <span style="font-size:.85rem; color:var(--ch-ink-soft);">Secure hosted gateway &middot; Apple Pay, Google Pay &amp; cards</span>
                            </div>
                        </div>
                        <span class="ch-chip ch-chip-recommended">Recommended</span>
                    </div>
                    <p class="ch-hosted-lead">Prefer to pay on Stripe's secure page? Continue there to pay your refundable deposit of <strong>&pound;{{ number_format($deposit, 2) }}</strong>{!! $balanceDueNote !!}.</p>
                    <a href="{{ $checkoutUrl }}" class="btn-ch-pay">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2l7 3v6c0 5-3 8.5-7 10-4-1.5-7-5-7-10V5z"/></svg>
                        <span>Pay &pound;{{ number_format($deposit, 2) }} via Stripe Checkout</span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="M13 6l6 6-6 6"/></svg>
                    </a>
                    <div class="ch-chip-row">
                        <span class="ch-chip">Visa</span>
                        <span class="ch-chip">Mastercard</span>
                        <span class="ch-chip">American Express</span>
                        <span class="ch-chip">Apple Pay</span>
                        <span class="ch-chip">Google Pay</span>
                    </div>
                </div>
            @endif

            <!-- Direct card payment with Stripe Payment Element -->
            <div class="ch-card-premium">
                <div class="ch-card-head">
                    <h2 class="ch-card-header-title">
                        <span class="ch-step-badge">2</span>
                        Pay with Card on This Page
                    </h2>
                </div>

                @if ($paymentIntentSecret)
                    <form id="directCardForm" method="POST" action="{{ route('booking.checkout.confirm', $reservation) }}" novalidate data-skip-loading-state>
                        @csrf

                        <div class="ch-form-group">
                            <label class="ch-form-label" for="payment-element-wrap">
                                <span>Card Details</span>
                                <span style="font-weight:600; color:var(--ch-ink-soft);">Powered by Stripe</span>
                            </label>
                            <div class="ch-stripe-element" id="payment-element-wrap" aria-label="Card payment form">
                                <div id="payment-element"></div>
                            </div>
                            <div class="ch-field-help">Your card details are encrypted by Stripe and never touch our servers.</div>
                        </div>

                        <div id="cardErrors" class="ch-form-error" role="alert" hidden></div>

                        <label class="ch-check" for="termsCheck">
                            <input type="checkbox" id="termsCheck" required checked>
                            <span class="ch-check-text">
                                I confirm the stay details and authorise the charge of
                                <strong>&pound;{{ number_format($deposit, 2) }}</strong>
                                via Stripe.{!! $balanceDueSentence !!}
                            </span>
                        </label>

                        <button type="submit" class="btn-ch-pay" id="confirmPayBtn">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2l7 3v6c0 5-3 8.5-7 10-4-1.5-7-5-7-10V5z"/></svg>
                            <span id="confirmPayText">Confirm Deposit &middot; &pound;{{ number_format($deposit, 2) }}</span>
                        </button>

                        <div class="ch-pay-trust">
                            <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 018 0v4"/></svg>256-Bit SSL Encryption</span>
                            <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2l7 3v6c0 5-3 8.5-7 10-4-1.5-7-5-7-10V5z"/><path d="M9 12l2 2 4-4"/></svg>PCI-DSS Compliant</span>
                            <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2l7 3v6c0 5-3 8.5-7 10-4-1.5-7-5-7-10V5z"/><path d="M9 12l2 2 4-4"/></svg>Fraud Protection</span>
                        </div>
                    </form>
                @else
                    <div class="ch-alert ch-alert-warning" role="alert">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
                        <div>
                            <span class="ch-alert-strong">In-page card payment is temporarily unavailable</span>
                            <span>Please use the Stripe Checkout option above to complete your reservation securely.</span>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Sidebar: order summary -->
        <aside class="ch-checkout-side">
            <div class="ch-summary-card">
                <div class="ch-summary-media">
                    @php($roomHero = $reservation->room?->images->first())
                    @if ($roomHero)
                        <img src="{{ asset('storage/'.$roomHero->path) }}" alt="{{ $reservation->room?->name }}">
                    @else
                        <div style="display:flex; align-items:center; justify-content:center; height:100%; color:#fff;">
                            <svg viewBox="0 0 24 24" width="46" height="46" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 11l9-7 9 7"/><path d="M5 10v10h14V10"/><path d="M10 20v-6h4v6"/></svg>
                        </div>
                    @endif
                    <div class="ch-summary-media-overlay"></div>
                    <h3 class="ch-summary-media-title">{{ $reservation->room?->name ?? 'Corner House Stay' }}</h3>
                </div>

                <div class="ch-summary-body">
                    <div class="ch-date-pill">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/></svg>
                        <span>{{ $reservation->check_in?->format('d M Y') }} &rarr; {{ $reservation->check_out?->format('d M Y') }}</span>
                    </div>
                    <div class="ch-stay-note">{{ $nights }} night{{ $nights > 1 ? 's' : '' }} &middot; {{ $reservation->guests_count }} guest{{ $reservation->guests_count > 1 ? 's' : '' }}</div>

                    <div class="ch-breakdown-row">
                        <span>Accommodation Stay ({{ $nights }} night{{ $nights > 1 ? 's' : '' }})</span>
                        <span>&pound;{{ number_format((float) $reservation->base_amount, 2) }}</span>
                    </div>

                    @if ((float) $reservation->discount_amount > 0)
                        <div class="ch-breakdown-row discount">
                            <span>Direct-booking discount ({{ \App\Models\Setting::getValue('direct_booking_discount', 10) }}%)</span>
                            <span>-&pound;{{ number_format((float) $reservation->discount_amount, 2) }}</span>
                        </div>
                    @endif

                    @if ((float) $reservation->fees_amount > 0)
                        <div class="ch-breakdown-row">
                            <span>Cleaning Fee</span>
                            <span>&pound;{{ number_format((float) $reservation->fees_amount, 2) }}</span>
                        </div>
                    @endif

                    @if ($reservation->relationLoaded('addons') && $reservation->addons->isNotEmpty())
                        @foreach ($reservation->addons as $addon)
                            <div class="ch-breakdown-row" style="color:var(--ch-ivy-soft);">
                                <span>+ {{ $addon->name }}</span>
                                <span>&pound;{{ number_format((float) ($addon->pivot->total_price ?? $addon->price), 2) }}</span>
                            </div>
                        @endforeach
                    @endif

                    @if ((float) $deposit > 0)
                        <div class="ch-breakdown-row deposit" style="border-top:1px solid var(--ch-line); border-bottom:1px solid var(--ch-line); padding:.7rem 0; margin:.6rem 0;">
                            <span>Refundable Security Deposit &mdash; due now</span>
                            <span style="font-weight:700; color:var(--ch-ink);">&pound;{{ number_format((float) $deposit, 2) }}</span>
                        </div>
                    @endif

                    @if ((float) $reservation->tax_amount > 0)
                        <div class="ch-breakdown-row" style="font-size:.85rem; color:var(--ch-ink-soft);">
                            <span>Taxes &amp; VAT</span>
                            <span>&pound;{{ number_format((float) $reservation->tax_amount, 2) }}</span>
                        </div>
                    @endif

                    <div class="ch-breakdown-row total-row">
                        <div>
                            <span class="ch-summary-sub" style="display:block; font-family:var(--ch-serif); font-size:1.02rem; color:var(--ch-ink); font-weight:700;">Grand Total</span>
                            <span class="ch-summary-sub">Includes stay, deposit &amp; tax</span>
                        </div>
                        <span class="ch-total-price">&pound;{{ number_format((float) $reservation->total_amount, 2) }}</span>
                    </div>

                    @if ($balanceDue > 0)
                        <div class="ch-breakdown-row" style="border-top:1px solid var(--ch-line); margin-top:.6rem; padding-top:.6rem; font-size:.85rem; color:var(--ch-ink-soft);">
                            <span>Balance due before arrival</span>
                            <span style="font-weight:700; color:var(--ch-terracotta-deep);">&pound;{{ number_format($balanceDue, 2) }}</span>
                        </div>
                    @endif

                    <div class="ch-badge-guarantee">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6L9 17l-5-5"/></svg>
                        <div style="font-size:.85rem;">
                            <strong style="display:block; margin-bottom:.15rem;">Lowest Direct Rate</strong>
                            <span>You are saving {{ \App\Models\Setting::getValue('direct_booking_discount', 10) }}% off platform prices by booking direct with Corner House.</span>
                        </div>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const key = @json($stripeKey);
    const secret = @json($paymentIntentSecret);
    const returnUrl = @json($paymentReturnUrl);
    const confirmUrl = @json(route('booking.checkout.confirm', $reservation));
    const csrfToken = @json(csrf_token());

    const form = document.getElementById('directCardForm');
    const btn = document.getElementById('confirmPayBtn');
    const btnText = document.getElementById('confirmPayText');
    const errorsBox = document.getElementById('cardErrors');

    function showError(msg) {
        errorsBox.textContent = msg;
        errorsBox.hidden = false;
    }
    function setLoading(loading) {
        btn.disabled = loading;
        if (loading) {
            btnText.textContent = 'Processing Payment\u2026';
        } else {
            btnText.textContent = btnText.dataset.original;
        }
    }

    if (!form || !key || !secret) {
        return;
    }

    btnText.dataset.original = btnText.textContent;
    btn.disabled = true;

    const script = document.createElement('script');
    script.src = 'https://js.stripe.com/v3/';
    script.async = true;
    script.onload = function () {
        if (!window.Stripe) { return; }
        const stripe = window.Stripe(key);
        const elements = stripe.elements({
            clientSecret: secret,
            appearance: {
                theme: 'flat',
                variables: { colorPrimary: '#B4552B', colorBackground: '#F7F4EC', colorText: '#1E211C', borderRadius: '8px' },
            },
        });
        const paymentElement = elements.create('payment', { layout: 'tabs' });
        paymentElement.mount('#payment-element');
        paymentElement.on('ready', function () { btn.disabled = false; });
        paymentElement.on('change', function (e) { btn.disabled = !e.complete; });

        form.addEventListener('submit', async function (ev) {
            ev.preventDefault();
            if (btn.disabled) { return; }
            setLoading(true);
            if (errorsBox) { errorsBox.hidden = true; }

            try {
                const result = await stripe.confirmPayment({
                    elements: elements,
                    confirmParams: {
                        return_url: returnUrl,
                        receipt_email: @json($guestEmail) || undefined,
                    },
                    redirect: 'if_required',
                });

                if (result.error) {
                    throw new Error(result.error.message || 'Payment could not be processed.');
                }

                if (result.paymentIntent && result.paymentIntent.status === 'succeeded') {
                    try {
                        await fetch(confirmUrl, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ payment_intent_id: result.paymentIntent.id }),
                        });
                    } catch (e) {
                        /* server-side confirmation is recovered on the return page */
                    }
                    window.location.assign(returnUrl);
                    return;
                }

                /* 3-D Secure challenge is the only other outcome with redirect 'if_required' */
                window.location.assign(returnUrl);
            } catch (e) {
                showError(e && e.message ? e.message : 'Payment could not be completed. Please try again.');
                setLoading(false);
            }
        });
    };
    script.onerror = function () {
        showError('Unable to load Stripe securely. Please use the Stripe Checkout option above.');
        setLoading(false);
    };
    document.head.appendChild(script);
});
</script>
@endsection