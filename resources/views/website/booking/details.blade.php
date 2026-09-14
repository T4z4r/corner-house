@extends('layouts.website.app')
@section('title', 'Checkout & Payment')
@section('content')
@include('website._page-hero', ['kicker' => 'Secure Checkout', 'title' => 'Guest Details & Payment'])
<div class="container ch-section">
    <!-- Step Progress Indicator -->
    <div class="row justify-content-center mb-5">
        <div class="col-lg-10">
            <div class="d-flex align-items-center justify-content-between position-relative px-3 px-md-5">
                <div class="text-center position-relative z-1">
                    <div class="rounded-circle bg-success text-white d-inline-flex align-items-center justify-content-center shadow-sm" style="width: 38px; height: 38px; font-weight: 600;">
                        <i class="bi bi-check-lg"></i>
                    </div>
                    <div class="small fw-semibold mt-2 text-dark">1. Select Dates</div>
                </div>
                <div class="flex-grow-1 mx-2" style="height: 3px; background: var(--ch-forest, #2d4d3a);"></div>
                <div class="text-center position-relative z-1">
                    <div class="rounded-circle text-white d-inline-flex align-items-center justify-content-center shadow-sm" style="width: 38px; height: 38px; font-weight: 600; background: var(--ch-forest, #2d4d3a);">
                        2
                    </div>
                    <div class="small fw-semibold mt-2 text-dark">2. Details &amp; Add-ons</div>
                </div>
                <div class="flex-grow-1 mx-2" style="height: 3px; background: #e0e0e0;"></div>
                <div class="text-center position-relative z-1">
                    <div class="rounded-circle bg-light text-muted border d-inline-flex align-items-center justify-content-center" style="width: 38px; height: 38px; font-weight: 600;">
                        3
                    </div>
                    <div class="small text-muted mt-2">3. Stripe Payment</div>
                </div>
            </div>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger mb-4 rounded-3 shadow-sm">
            <div class="fw-semibold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i>Please check your booking details:</div>
            <ul class="mb-0 ps-3 small">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (request()->query('cancelled'))
        <div class="alert alert-warning mb-4 rounded-3 shadow-sm d-flex align-items-center gap-2">
            <i class="bi bi-info-circle-fill fs-5 text-warning"></i>
            <div>Payment was cancelled on Stripe. Your details are preserved below so you can try again whenever you are ready.</div>
        </div>
    @endif

    @php
        $addons = \App\Models\AddOn::query()->where('is_active', true)->orderBy('sort_order')->get();
    @endphp
    <div class="row g-5">
        <div class="col-lg-7">
            <form method="POST" action="{{ route('booking.pay') }}" class="ch-form-card" id="bookingForm">
                @csrf
                <input type="hidden" name="room_id" value="{{ $room->id }}">
                <input type="hidden" name="check_in" value="{{ $checkIn->toDateString() }}">
                <input type="hidden" name="check_out" value="{{ $checkOut->toDateString() }}">
                <input type="hidden" name="guests_count" value="{{ $guests }}">

                <!-- Section 1: Guest Information -->
                <div class="mb-4">
                    <h5 class="mb-1 d-flex align-items-center gap-2" style="font-family:'Cormorant Garamond',serif;font-size:1.4rem;">
                        <span class="badge rounded-circle bg-dark text-white" style="width:26px; height:26px; font-size:0.85rem; font-family:sans-serif;">1</span>
                        Guest Information
                    </h5>
                    <p class="text-muted small mb-3">Who is the lead guest for this booking?</p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">First name <span class="text-danger">*</span></label>
                            <input type="text" name="guest_first_name" class="form-control" value="{{ old('guest_first_name') }}" placeholder="Alex" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Last name <span class="text-danger">*</span></label>
                            <input type="text" name="guest_last_name" class="form-control" value="{{ old('guest_last_name') }}" placeholder="Smith" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Email address <span class="text-danger">*</span></label>
                            <input type="email" name="guest_email" class="form-control" value="{{ old('guest_email') }}" placeholder="alex@example.com" required>
                            <div class="form-text small">Booking &amp; payment receipts will be sent here.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Phone number</label>
                            <input type="tel" name="guest_phone" class="form-control" value="{{ old('guest_phone') }}" placeholder="+44 7700 900123">
                            <div class="form-text small">Used for pre-arrival SMS &amp; check-in details.</div>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Add-ons / Enhancements -->
                @if ($addons->isNotEmpty())
                    <div class="mt-4 pt-4 border-top mb-4">
                        <h5 class="mb-1 d-flex align-items-center gap-2" style="font-family:'Cormorant Garamond',serif;font-size:1.4rem;">
                            <span class="badge rounded-circle bg-dark text-white" style="width:26px; height:26px; font-size:0.85rem; font-family:sans-serif;">2</span>
                            Enhance Your Stay <span class="text-muted fw-normal fs-6">(Optional)</span>
                        </h5>
                        <p class="text-muted small mb-3">Select drinks packages, hampers, or experiences to add to your stay.</p>
                        <div class="row g-3">
                            @foreach ($addons as $addon)
                                <div class="col-sm-6">
                                    <label class="ch-addon-option d-flex gap-3 p-3 border rounded-3 bg-light cursor-pointer h-100" for="addon_{{ $addon->id }}">
                                        <input type="checkbox" name="addon_ids[]" value="{{ $addon->id }}" id="addon_{{ $addon->id }}" class="form-check-input mt-1 addon-check" data-price="{{ $addon->price }}">
                                        <div class="flex-grow-1">
                                            <strong class="d-block text-dark" style="font-size:0.92rem;">{{ $addon->name }}</strong>
                                            <span class="text-muted small d-block mb-1">{{ $addon->description ? \Illuminate\Support\Str::limit($addon->description, 60) : '' }}</span>
                                            <div class="fw-bold" style="color:var(--ch-forest, #2d4d3a); font-size:0.88rem;">
                                                +£{{ number_format($addon->price, 2) }}
                                                @if ($addon->unit)
                                                    <span class="text-muted fw-normal">/ {{ $addon->unit }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Section 3: Stripe Payment Method -->
                <div class="mt-4 pt-4 border-top">
                    <h5 class="mb-1 d-flex align-items-center gap-2" style="font-family:'Cormorant Garamond',serif;font-size:1.4rem;">
                        <span class="badge rounded-circle bg-dark text-white" style="width:26px; height:26px; font-size:0.85rem; font-family:sans-serif;">{{ $addons->isNotEmpty() ? '3' : '2' }}</span>
                        Payment Method
                    </h5>
                    <p class="text-muted small mb-3">All payments are processed securely via Stripe. Your card is charged instantly upon checkout.</p>

                    <div class="p-3 border border-2 border-primary rounded-3 bg-white mb-4">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-shield-check-fill fs-4 text-primary"></i>
                                <div>
                                    <strong class="d-block">Stripe Secure Checkout</strong>
                                    <span class="text-muted small">Credit card, debit card, Apple Pay, Google Pay</span>
                                </div>
                            </div>
                            <span class="badge text-bg-primary">Secure</span>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-2 mt-3 pt-2 border-top">
                            <span class="badge bg-light text-dark border px-2 py-1"><i class="bi bi-credit-card me-1"></i>Visa</span>
                            <span class="badge bg-light text-dark border px-2 py-1"><i class="bi bi-credit-card me-1"></i>Mastercard</span>
                            <span class="badge bg-light text-dark border px-2 py-1"><i class="bi bi-credit-card me-1"></i>American Express</span>
                            <span class="badge bg-light text-dark border px-2 py-1"><i class="bi bi-apple me-1"></i>Apple Pay</span>
                            <span class="badge bg-light text-dark border px-2 py-1"><i class="bi bi-google me-1"></i>Google Pay</span>
                        </div>
                    </div>

                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" name="agree_terms" id="agree_terms" required>
                        <label class="form-check-label small text-muted" for="agree_terms">
                            I agree to the <a href="#house-rules" class="text-decoration-underline">House Rules</a> and acknowledge that direct bookings carry a 10% discount with a 30% non-refundable deposit on cancellation outside 5 days.
                        </label>
                    </div>

                    <button class="btn btn-ch-book w-100 py-3 fs-5 shadow" type="submit" id="submitPaymentBtn">
                        <i class="bi bi-shield-lock-fill me-2"></i>Proceed to Stripe Secure Checkout
                    </button>

                    <div class="d-flex align-items-center justify-content-center gap-3 mt-3 text-muted small">
                        <span><i class="bi bi-lock-fill text-success me-1"></i>256-Bit SSL Encrypted</span>
                        <span>·</span>
                        <span><i class="bi bi-shield-check me-1"></i>PCI DSS Level 1 Certified</span>
                    </div>
                </div>
            </form>
        </div>

        <!-- Right Column: Order Summary -->
        <div class="col-lg-5">
            <div class="ch-booking-card shadow-sm border-0 sticky-top" style="top: 2rem;">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h2>{{ $room->name }}</h2>
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Direct Discount</span>
                </div>
                <p class="ch-suite-meta mb-1"><i class="bi bi-calendar-event me-1"></i>{{ $checkIn->format('d M Y') }} → {{ $checkOut->format('d M Y') }}</p>
                <p class="ch-suite-meta mb-3"><i class="bi bi-people me-1"></i>{{ $quote['nights'] }} night(s) · {{ $guests }} guest(s)</p>
                
                <hr>

                <h6 class="mb-2 font-serif fw-semibold"><i class="bi bi-list-stars me-1"></i>Nightly Breakdown</h6>
                <div class="bg-light p-2 rounded-2 mb-3">
                    @foreach ($quote['per_night'] as $date => $rate)
                        <div class="d-flex justify-content-between small text-muted py-1">
                            <span>{{ \Carbon\Carbon::parse($date)->format('D j M Y') }}</span>
                            <span class="fw-semibold text-dark">£{{ number_format($rate, 2) }}</span>
                        </div>
                    @endforeach
                </div>

                <div class="d-flex justify-content-between py-1">
                    <span>Accommodation Stay</span>
                    <span>£{{ number_format($quote['base_amount'], 2) }}</span>
                </div>

                @if ($quote['discount_amount'] > 0)
                    <div class="d-flex justify-content-between py-1 text-success">
                        <span>Direct-booking discount ({{ \App\Models\Setting::getValue('direct_booking_discount', 10) }}%)</span>
                        <span class="fw-bold">-£{{ number_format($quote['discount_amount'], 2) }}</span>
                    </div>
                @endif

                @if ($quote['fees_amount'] > 0)
                    <div class="d-flex justify-content-between py-1">
                        <span>Cleaning fee</span>
                        <span>£{{ number_format($quote['fees_amount'], 2) }}</span>
                    </div>
                @endif

                <div id="addonsSummary"></div>

                @if (! empty($quote['damage_deposit']) && $quote['damage_deposit'] > 0)
                    <div class="d-flex justify-content-between py-1 text-muted border-top border-bottom my-2 py-2 small">
                        <span><i class="bi bi-info-circle me-1"></i>Damage deposit (refundable)</span>
                        <span class="fw-semibold text-dark">£{{ number_format($quote['damage_deposit'], 2) }}</span>
                    </div>
                @endif

                <div class="d-flex justify-content-between py-1">
                    <span>Taxes &amp; VAT</span>
                    <span>£{{ number_format($quote['tax_amount'], 2) }}</span>
                </div>

                <hr class="my-3">

                <div class="d-flex justify-content-between align-items-center ch-price">
                    <div>
                        <span class="d-block fs-5 fw-bold text-dark">Total Amount</span>
                        <span class="text-muted small font-sans">Includes taxes &amp; deposit</span>
                    </div>
                    <span class="fs-2 fw-bold text-dark" id="totalDisplay">£{{ number_format($quote['total'], 2) }}</span>
                </div>
                <input type="hidden" id="baseTotal" value="{{ $quote['total'] }}">

                <div class="p-3 bg-light rounded-3 mt-3 border">
                    <div class="d-flex gap-2">
                        <i class="bi bi-patch-check-fill text-success fs-5"></i>
                        <div class="small">
                            <strong>Direct Booking Guarantee:</strong> You are receiving our lowest rate guaranteed, with {{ \App\Models\Setting::getValue('direct_booking_discount', 10) }}% off platform prices.
                        </div>
                    </div>
                </div>
            </div>

            <!-- House Rules Accordion / Card -->
            <div class="ch-booking-card mt-3">
                <h6 class="mb-2 fw-semibold"><i class="bi bi-house-door me-1"></i>House rules summary</h6>
                <ul class="small text-muted mb-0 ps-3">
                    <li>Check-in from 3:00 PM · Check-out by 12:00 PM</li>
                    <li>Minimum stay: {{ \App\Models\Setting::getValue('min_stay_nights', 2) }} nights</li>
                    <li>Maximum guests: {{ \App\Models\Setting::getValue('max_adults', 12) }} adults</li>
                    <li>No pets or smoking indoors</li>
                </ul>
            </div>
        </div>
    </div>
</div>

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
                const name = cb.closest('label').querySelector('strong').textContent;
                html += '<div class="d-flex justify-content-between py-1 text-primary"><span>' + name + '</span><span>£' + price.toFixed(2) + '</span></div>';
            }
        });
        summaryEl.innerHTML = html;
        totalEl.textContent = '£' + (baseTotal + addonTotal).toFixed(2);
    }

    checks.forEach(function (cb) {
        cb.addEventListener('change', recalc);
    });

    if (form && submitBtn) {
        form.addEventListener('submit', function () {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Redirecting to Stripe Secure Payment...';
        });
    }
});
</script>
@endpush
@endsection
