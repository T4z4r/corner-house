@extends('layouts.website.app')
@section('title', 'Guest Details & Stay Options')
@section('robots', 'noindex, nofollow')
@section('content')

@include('website._page-hero', ['kicker' => 'Direct Booking · Step 2 of 3', 'title' => 'Guest Details & Stay Options', 'subtitle' => 'Confirm your guest details, select optional add-ons, and proceed to instant secure payment.'])

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
  background: var(--ch-line);
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
  transition: all 0.3s ease;
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
.ch-step-badge {
  background: var(--ch-ivy-deep);
  color: var(--ch-stone-light);
  width: 28px;
  height: 28px;
  border-radius: 50%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 0.85rem;
  font-weight: 600;
  font-family: var(--body);
}

/* Custom Input Styling */
.ch-form-group {
  margin-bottom: 1.2rem;
}
.ch-form-label {
  display: block;
  font-size: 0.78rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: var(--ch-ivy-deep);
  margin-bottom: 0.4rem;
}
.ch-form-control {
  width: 100%;
  padding: 0.85rem 1rem;
  font-family: var(--body);
  font-size: 1rem;
  color: var(--ch-ink);
  background: var(--ch-stone-light);
  border: 1px solid rgba(31,56,38,0.2);
  border-radius: 6px;
  transition: all 0.2s ease;
}
.ch-form-control:focus {
  outline: none;
  background: #ffffff;
  border-color: var(--ch-ivy-deep);
  box-shadow: 0 0 0 3px rgba(31,56,38,0.12);
}

.ch-phone-row {
  display: grid;
  grid-template-columns: 155px 1fr;
  gap: 0.5rem;
}
.ch-phone-code {
  padding: 0.85rem 0.7rem;
  cursor: pointer;
}
@media (max-width: 480px) {
  .ch-phone-row { grid-template-columns: 1fr; }
}

/* Addon Selection Cards */
.ch-addon-card {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  padding: 1.1rem 1.2rem;
  background: var(--ch-stone-light);
  border: 1px solid var(--ch-line);
  border-radius: 8px;
  cursor: pointer;
  height: 100%;
  transition: all 0.2s ease;
}
.ch-addon-card:hover {
  border-color: var(--ch-ivy-soft);
  background: #ffffff;
  transform: translateY(-2px);
  box-shadow: 0 8px 20px -8px rgba(31,56,38,0.12);
}
.ch-addon-card input[type="checkbox"] {
  width: 20px;
  height: 20px;
  margin-top: 3px;
  accent-color: var(--ch-ivy-deep);
  cursor: pointer;
}

/* Payment Method Highlight Box */
.ch-payment-box {
  background: linear-gradient(135deg, rgba(31,56,38,0.04) 0%, rgba(180,85,43,0.04) 100%);
  border: 1.5px solid var(--ch-ivy-soft);
  border-radius: 10px;
  padding: 1.4rem;
}

/* Submit Button */
.btn-ch-pay {
  background: linear-gradient(145deg, var(--ch-terracotta), var(--ch-terracotta-deep));
  color: #ffffff;
  font-family: var(--body);
  font-weight: 700;
  font-size: 1.1rem;
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

/* Enquiry submission modals */
.ch-modal {
  border: none;
  border-radius: 16px;
  padding: 0;
  max-width: 460px;
  width: 100%;
  box-shadow: 0 24px 70px -20px rgba(31,56,38,0.35);
  color: var(--ch-ink);
}
.ch-modal::backdrop {
  background: rgba(31,56,38,0.55);
  backdrop-filter: blur(2px);
}
.ch-modal-inner {
  padding: 2.5rem 2.1rem;
  text-align: center;
}
.ch-modal-icon {
  width: 68px;
  height: 68px;
  border-radius: 50%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 1.9rem;
  margin-bottom: 1.1rem;
}
.ch-modal-icon.success {
  background: var(--ch-ivy-deep);
  color: #ffffff;
}
.ch-modal-icon.failure {
  background: var(--ch-terracotta-tint);
  color: var(--ch-terracotta-deep);
}
.ch-modal h3 {
  font-family: "Fraunces", Georgia, serif;
  font-size: 1.5rem;
  color: var(--ch-ivy-deep);
  margin-bottom: 0.4rem;
}
.ch-modal-text {
  color: var(--ch-ink-soft);
  font-size: 0.95rem;
  line-height: 1.65;
  margin-bottom: 0.25rem;
}
.ch-modal-ref {
  display: inline-block;
  margin-top: 0.9rem;
  padding: 0.4rem 1rem;
  background: var(--ch-stone-light);
  border: 1px solid var(--ch-sage);
  border-radius: 999px;
  font-size: 0.8rem;
  font-weight: 700;
  letter-spacing: 0.06em;
  color: var(--ch-ivy-deep);
}
.ch-modal-actions {
  margin-top: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 0.7rem;
}
.ch-modal-actions .btn-ch-pay {
  width: 100%;
  padding: 0.95rem 1.4rem;
  font-size: 1rem;
  text-decoration: none;
}
.ch-modal-actions .btn-ch-ghost {
  width: 100%;
  padding: 0.85rem 1.4rem;
  font-size: 0.95rem;
  border-radius: 8px;
  border: 1px solid var(--ch-line);
  background: var(--ch-stone-light);
  color: var(--ch-ivy-deep);
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s ease;
}
.ch-modal-actions .btn-ch-ghost:hover {
  border-color: var(--ch-ivy-soft);
  background: #ffffff;
}
</style>

<div class="wrap ch-checkout-container">

    <!-- 3-Step Luxury Progress Bar -->
    <div class="ch-stepper">
        <div class="ch-step-item completed">
            <div class="ch-step-circle"><i class="bi bi-check-lg"></i></div>
            <span class="ch-step-label">1. Dates</span>
        </div>
        <div class="ch-step-item active">
            <div class="ch-step-circle">2</div>
            <span class="ch-step-label">2. Details</span>
        </div>
        <div class="ch-step-item">
            <div class="ch-step-circle">3</div>
            <span class="ch-step-label">3. Confirmation</span>
        </div>
    </div>

    <!-- Error Alerts -->
    @if ($errors->any())
        <div class="alert alert-danger mb-4 rounded-3 shadow-sm border-0" style="background:#fdf2f2; color:#842029; border-left:4px solid #b4552b !important;">
            <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i>Please review your details:</div>
            <ul class="mb-0 ps-3 small">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (request()->query('cancelled'))
        <div class="alert alert-warning mb-4 rounded-3 shadow-sm border-0 d-flex align-items-center gap-3 p-3" style="background:#fff9e6; color:#664d03; border-left:4px solid #c9a227 !important;">
            <i class="bi bi-info-circle-fill fs-3" style="color:#c9a227;"></i>
            <div>
                <strong class="d-block">Payment was cancelled on Stripe</strong>
                <span>Your booking details have been preserved below. You can try again whenever you are ready.</span>
            </div>
        </div>
    @endif

    @php
        $addons = \App\Models\AddOn::query()->where('is_active', true)->orderBy('sort_order')->get();
        $roomHero = $room->images->first();
    @endphp

    <div class="row g-5">
        <!-- Main Form Column -->
        <div class="col-lg-7">
            <form method="POST" action="{{ route('booking.request') }}" id="bookingForm" data-skip-loading-state>
                @csrf
                <input type="hidden" name="room_id" value="{{ $room->id }}">
                <input type="hidden" name="check_in" value="{{ $checkIn->toDateString() }}">
                <input type="hidden" name="check_out" value="{{ $checkOut->toDateString() }}">
                <input type="hidden" name="guests_count" value="{{ $guests }}">

                <!-- Section 1: Lead Guest Information -->
                <div class="ch-card-premium mb-4">
                    <h2 class="ch-card-header-title">
                        <span class="ch-step-badge">1</span>
                        Lead Guest Details
                    </h2>
                    <p class="small text-muted mb-4">Please provide details for the lead guest under this reservation.</p>

                    <div class="row g-3">
                        <div class="col-md-6 ch-form-group">
                            <label class="ch-form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" name="guest_first_name" class="ch-form-control" value="{{ old('guest_first_name') }}" placeholder="e.g. Alex" required>
                        </div>
                        <div class="col-md-6 ch-form-group">
                            <label class="ch-form-label">Last Name <span class="text-danger">*</span></label>
                            <input type="text" name="guest_last_name" class="ch-form-control" value="{{ old('guest_last_name') }}" placeholder="e.g. Smith" required>
                        </div>
                        <div class="col-md-6 ch-form-group">
                            <label class="ch-form-label">Email Address <span class="text-danger">*</span></label>
                            <input type="email" name="guest_email" class="ch-form-control" value="{{ old('guest_email') }}" placeholder="alex@example.com" required>
                            <div class="small text-muted mt-1">Confirmation and receipts will be sent to this address.</div>
                        </div>
                        <div class="col-md-6 ch-form-group">
                            <label class="ch-form-label">Phone Number</label>
                            <div class="ch-phone-row">
                                <select name="phone_code" id="phoneCode" class="ch-form-control ch-phone-code" aria-label="Country dial code">
                                    <option value="+44" selected>United Kingdom +44</option>
                                    <option value="+353">Ireland +353</option>
                                    <option value="+1">United States +1</option>
                                    <option value="+1">Canada +1</option>
                                    <option value="+61">Australia +61</option>
                                    <option value="+64">New Zealand +64</option>
                                    <option value="+33">France +33</option>
                                    <option value="+49">Germany +49</option>
                                    <option value="+34">Spain +34</option>
                                    <option value="+31">Netherlands +31</option>
                                    <option value="+971">United Arab Emirates +971</option>
                                    <option value="+966">Saudi Arabia +966</option>
                                </select>
                                <input type="tel" name="guest_phone" id="guestPhone" class="ch-form-control" value="{{ old('guest_phone') }}" placeholder="7700 900 123" inputmode="tel" autocomplete="tel">
                            </div>
                            <div class="small text-muted mt-1">Dial code defaults to United Kingdom (+44). Used for pre-arrival notification and key access.</div>
                        </div>
                    </div>

                        <div class="ch-form-group mb-0">
                            <label class="ch-form-label" for="message">Occasion or message</label>
                            <textarea name="message" id="message" rows="3" class="ch-form-control" placeholder="Birthday, family get-together, walking weekend &hellip; anything that helps us prepare for your stay.">{{ old('message') }}</textarea>
                            <div class="small text-muted mt-1">No payment is needed now &mdash; we will email you a payment link after reviewing your request.</div>
                        </div>
                </div>

                <!-- Section 2: Optional Enhancements -->
                @if ($addons->isNotEmpty())
                    <div class="ch-card-premium mb-4">
                        <h2 class="ch-card-header-title">
                            <span class="ch-step-badge">2</span>
                            Enhance Your Stay <span class="fw-normal fs-6 text-muted">(Optional)</span>
                        </h2>
                        <p class="small text-muted mb-4">Select welcome packages, drinks, or hampers to have ready upon arrival.</p>

                        <div class="row g-3">
                            @foreach ($addons as $addon)
                                <div class="col-sm-6">
                                    <label class="ch-addon-card" for="addon_{{ $addon->id }}">
                                        <input type="checkbox" name="addon_ids[]" value="{{ $addon->id }}" id="addon_{{ $addon->id }}" class="addon-check" data-price="{{ $addon->price }}">
                                        <div>
                                            <strong class="d-block" style="color:var(--ch-ivy-deep); font-size:0.95rem;">{{ $addon->name }}</strong>
                                            <span class="small text-muted d-block mb-2">{{ $addon->description ? \Illuminate\Support\Str::limit($addon->description, 65) : '' }}</span>
                                            <div class="fw-bold" style="color:var(--ch-terracotta); font-size:0.9rem;">
                                                +£{{ number_format($addon->price, 2) }}
                                                @if ($addon->unit)
                                                    <span class="fw-normal text-muted" style="font-size:0.8rem;">/ {{ $addon->unit }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Section 3: Booking Request -->
                <div class="ch-card-premium">
                    <h2 class="ch-card-header-title">
                        <span class="ch-step-badge">{{ $addons->isNotEmpty() ? '3' : '2' }}</span>
                        Send Your Booking Request
                    </h2>
                    <p class="small text-muted mb-4">No payment is taken now. Your dates are held for 48 hours while we review your request, then we email you a secure payment link.</p>

                    <div class="ch-payment-box mb-4">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="d-flex align-items-center gap-3">
                                <i class="bi bi-clock-history fs-3" style="color:var(--ch-ivy-deep);"></i>
                                <div>
                                    <strong class="d-block text-dark" style="font-family:'Fraunces',serif; font-size:1.15rem;">Enquiry-first booking</strong>
                                    <span class="small text-muted">Dates held 48 hours &middot; no charge until we confirm</span>
                                </div>
                            </div>
                            <span class="badge" style="background:var(--ch-ivy-deep); color:var(--ch-stone-light); font-size:0.75rem; letter-spacing:0.06em; text-transform:uppercase; padding:0.35rem 0.7rem;">No payment now</span>
                        </div>
                        <div class="small text-muted border-top pt-3" style="border-color:var(--ch-line) !important;">
                            <strong class="d-block text-dark mb-1">What happens next?</strong>
                            <ol class="mb-0 ps-3">
                                <li>We review your request and hold your dates for 48 hours.</li>
                                <li>We ask the lead guest for a photo ID and a signed rental agreement.</li>
                                <li>We email you a payment link (or you pay on this website) to secure the booking with a refundable &pound;950 deposit.</li>
                            </ol>
                        </div>
                    </div>

                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" name="agree" id="agree_terms" value="1" required checked>
                        <label class="form-check-label small text-muted" for="agree_terms">
                            I have read the <a href="#house-rules" class="text-decoration-underline" style="color:var(--ch-terracotta-deep);">House Rules</a> and the <a href="{{ route('terms') }}" class="text-decoration-underline" style="color:var(--ch-terracotta-deep);">Terms and Conditions</a>, and acknowledge that direct bookings include a 10% direct-booking discount. Direct bookings require photo ID and a signed rental agreement.
                        </label>
                    </div>

                    <button class="btn-ch-pay" type="submit" id="submitPaymentBtn">
                        <i class="bi bi-envelope-check"></i>
                        <span>Submit Enquiry</span>
                        <i class="bi bi-arrow-right ms-1"></i>
                    </button>

                    <div class="d-flex align-items-center justify-content-center gap-4 mt-4 text-muted small">
                        <span><i class="bi bi-calendar-check me-1" style="color:var(--ch-ivy-deep);"></i>48-hour hold</span>
                        <span>·</span>
                        <span><i class="bi bi-shield-check me-1" style="color:var(--ch-ivy-deep);"></i>No charge today</span>
                    </div>
                </div>
            </form>
        </div>

        <!-- Right Column: Premium Order Summary Sidebar -->
        <div class="col-lg-5">
            <div class="ch-summary-card">
                <!-- Media Header -->
                <div class="ch-summary-media">
                    @if ($roomHero)
                        <img src="{{ asset('storage/'.$roomHero->path) }}" alt="{{ $room->name }}">
                    @else
                        <div class="d-flex align-items-center justify-content-center h-100 text-white fs-1"><i class="bi bi-house-heart"></i></div>
                    @endif
                    <div class="ch-summary-media-overlay"></div>
                    <h3 class="ch-summary-media-title">{{ $room->property?->name ?? 'Whole house' }}</h3>
                </div>

                <div class="ch-summary-body">
                    <!-- Stay Pill -->
                    <div class="ch-date-pill">
                        <i class="bi bi-calendar-check"></i>
                        <span>{{ $checkIn->format('d M Y') }} → {{ $checkOut->format('d M Y') }}</span>
                    </div>
                    <div class="small text-muted mb-3"><i class="bi bi-moon-stars me-1"></i>{{ $quote['nights'] }} night(s) · {{ $guests }} guest(s)</div>

                    <!-- Nightly Breakdown -->
                    <div class="p-2.5 rounded mb-3" style="background:var(--ch-stone-light); border:1px solid var(--ch-line);">
                        <div class="small font-bold text-uppercase mb-1" style="font-size:0.7rem; letter-spacing:0.08em; color:var(--ch-ivy-deep);">Nightly rate breakdown</div>
                        <div class="small text-muted mb-2">Direct rate &mdash; this price includes our {{ \App\Models\Setting::getValue('direct_booking_discount', 10) }}% direct-booking discount.</div>
                        @foreach ($quote['per_night'] as $date => $rate)
                            <div class="d-flex justify-content-between small text-muted py-0.5">
                                <span>{{ \Carbon\Carbon::parse($date)->format('D j M Y') }}</span>
                                <span class="fw-semibold text-dark">£{{ number_format($rate, 2) }}</span>
                            </div>
                        @endforeach
                    </div>

                    <!-- Itemized Rows -->
                    <div class="ch-breakdown-row">
                        <span>Accommodation Stay</span>
                        <span>£{{ number_format($quote['base_amount'], 2) }}</span>
                    </div>

                    @if ($quote['discount_amount'] > 0)
                        <div class="ch-breakdown-row discount">
                            <span>Direct-booking discount ({{ \App\Models\Setting::getValue('direct_booking_discount', 10) }}%)</span>
                            <span>-£{{ number_format($quote['discount_amount'], 2) }}</span>
                        </div>
                    @endif

                    @if ($quote['fees_amount'] > 0)
                        <div class="ch-breakdown-row">
                            <span>Cleaning Fee</span>
                            <span>£{{ number_format($quote['fees_amount'], 2) }}</span>
                        </div>
                    @endif

                    <div id="addonsSummary"></div>

                    @if (! empty($quote['damage_deposit']) && $quote['damage_deposit'] > 0)
                        <div class="ch-breakdown-row py-2 my-2 border-top border-bottom small text-muted">
                            <span><i class="bi bi-info-circle me-1"></i>Damage deposit (refundable)</span>
                            <span class="fw-bold text-dark">£{{ number_format($quote['damage_deposit'], 2) }}</span>
                        </div>
                    @endif

                    <div class="ch-breakdown-row text-muted small">
                        <span>Taxes &amp; VAT</span>
                        <span>£{{ number_format($quote['tax_amount'], 2) }}</span>
                    </div>

                    <!-- Grand Total -->
                    <div class="ch-breakdown-row total-row">
                        <div>
                            <span class="d-block fw-bold text-dark fs-6" style="font-family:'Fraunces',serif;">Grand Total</span>
                            <span class="small text-muted">Includes stay, deposit &amp; tax</span>
                        </div>
                        <span class="ch-total-price" id="totalDisplay">£{{ number_format($quote['total'], 2) }}</span>
                    </div>
                    <input type="hidden" id="baseTotal" value="{{ $quote['total'] }}">

                    <!-- Direct Guarantee Banner -->
                    <div class="ch-badge-guarantee">
                        <i class="bi bi-patch-check-fill"></i>
                        <div class="small">
                            <strong class="d-block text-dark mb-0.5">Best Rate Guaranteed</strong>
                            <span>You are saving {{ \App\Models\Setting::getValue('direct_booking_discount', 10) }}% off platform rates by booking direct with Corner House.</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- House Rules Accordion -->
            <div class="ch-card-premium mt-4 p-4" id="house-rules">
                <h4 class="mb-2 fs-6 fw-bold text-uppercase" style="letter-spacing:0.08em; color:var(--ch-ivy-deep);"><i class="bi bi-house-door me-2"></i>House Rules</h4>
                <ul class="small text-muted mb-0 ps-3">
                    <li class="mb-1">Check-in from 3:00 PM · Check-out by 12:00 PM</li>
                    <li class="mb-1">Minimum stay: {{ \App\Models\Setting::getValue('min_stay_nights', 2) }} nights (3 on bank holidays and seasonal events)</li>
                    <li class="mb-1">Maximum occupancy: {{ \App\Models\Setting::getValue('max_adults', 12) }} adults</li>
                    <li>No pets or indoor smoking permitted</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Success Modal -->
<dialog class="ch-modal" id="enquirySuccessModal" aria-labelledby="enquirySuccessTitle">
    <div class="ch-modal-inner">
        <div class="ch-modal-icon success"><i class="bi bi-check-lg"></i></div>
        <h3 id="enquirySuccessTitle">Your enquiry has been sent</h3>
        <p class="ch-modal-text">
            Thank you &mdash; your dates are held for 48 hours while we review your request. We will email you to confirm, then send a secure payment link for the refundable &pound;950 deposit.
        </p>
        <span class="ch-modal-ref" id="enquirySuccessRef"></span>
        <div class="ch-modal-actions">
            <a class="btn-ch-pay" id="enquirySuccessNext" href="{{ route('booking.requested') }}">View next steps <i class="bi bi-arrow-right ms-2"></i></a>
            <button type="button" class="btn-ch-ghost" data-close-modal="enquirySuccessModal">Close</button>
        </div>
    </div>
</dialog>

<!-- Failure Modal -->
<dialog class="ch-modal" id="enquiryFailureModal" aria-labelledby="enquiryFailureTitle">
    <div class="ch-modal-inner">
        <div class="ch-modal-icon failure"><i class="bi bi-exclamation-triangle"></i></div>
        <h3 id="enquiryFailureTitle">Sorry, we could not submit your enquiry</h3>
        <p class="ch-modal-text" id="enquiryFailureMessage">Please try again in a moment.</p>
        <div class="ch-modal-actions">
            <button type="button" class="btn-ch-ghost" data-close-modal="enquiryFailureModal">Try again</button>
        </div>
    </div>
</dialog>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const checks = document.querySelectorAll('.addon-check');
    const baseTotal = parseFloat(document.getElementById('baseTotal').value);
    const summaryEl = document.getElementById('addonsSummary');
    const totalEl = document.getElementById('totalDisplay');
    const form = document.getElementById('bookingForm');
    const submitBtn = document.getElementById('submitPaymentBtn');

    function recalc() {
        let addonTotal = 0;
        let html = '';
        checks.forEach(function (cb) {
            if (cb.checked) {
                const price = parseFloat(cb.dataset.price);
                addonTotal += price;
                const name = cb.closest('.ch-addon-card').querySelector('strong').textContent;
                html += '<div class="ch-breakdown-row text-primary"><span>+ ' + name + '</span><span>£' + price.toFixed(2) + '</span></div>';
            }
        });
        summaryEl.innerHTML = html;
        totalEl.textContent = '£' + (baseTotal + addonTotal).toFixed(2);
    }

    checks.forEach(function (cb) {
        cb.addEventListener('change', recalc);
    });

    if (form && submitBtn) {
        const successModal = document.getElementById('enquirySuccessModal');
        const failureModal = document.getElementById('enquiryFailureModal');
        const successRef = document.getElementById('enquirySuccessRef');
        const successNext = document.getElementById('enquirySuccessNext');
        const failureMessage = document.getElementById('enquiryFailureMessage');
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const originalBtnHtml = submitBtn.innerHTML;

        document.querySelectorAll('[data-close-modal]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const target = document.getElementById(btn.dataset.closeModal);
                if (target && target.open) target.close();
            });
        });

        function setLoading(loading) {
            submitBtn.disabled = loading;
            submitBtn.style.opacity = loading ? '0.8' : '1';
            submitBtn.innerHTML = loading
                ? '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Submitting enquiry...'
                : originalBtnHtml;
        }

        form.addEventListener('submit', async function (e) {
            e.preventDefault();

            const code = document.getElementById('phoneCode');
            const phone = document.getElementById('guestPhone');
            if (code && phone && phone.value && !phone.value.startsWith('+')) {
                phone.value = (code.value + ' ' + phone.value).trim();
            }

            if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
                form.reportValidity();
                return;
            }

            setLoading(true);

            try {
                const fd = new FormData(form);
                const r = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: fd,
                });

                let data = {};
                try { data = await r.json(); } catch (err) { /* non-JSON body */ }

                if (r.ok && data.status === 'ok') {
                    successRef.textContent = 'Request reference #' + data.enquiry_id;
                    const url = new URL(successNext.getAttribute('href'), window.location.origin);
                    url.searchParams.set('enquiry', String(data.enquiry_id));
                    successNext.href = url.href;
                    successModal.showModal();
                    return;
                }

                throw new Error(data.error || data.message || 'Your enquiry could not be submitted.');
            } catch (err) {
                failureMessage.textContent = err.message || 'Your enquiry could not be submitted. Please try again.';
                setLoading(false);
                failureModal.showModal();
            }
        });
    }
});
</script>
@endpush
@endsection
