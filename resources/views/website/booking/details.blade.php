@extends('layouts.website.app')
@section('title', 'Guest details')
@section('content')
@include('website._page-hero', ['kicker' => 'Checkout', 'title' => 'Your details'])
<div class="container ch-section">
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
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">First name</label>
                        <input type="text" name="guest_first_name" class="form-control" value="{{ old('guest_first_name') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Last name</label>
                        <input type="text" name="guest_last_name" class="form-control" value="{{ old('guest_last_name') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" name="guest_email" class="form-control" value="{{ old('guest_email') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone</label>
                        <input type="text" name="guest_phone" class="form-control" value="{{ old('guest_phone') }}">
                    </div>
                </div>

                @if ($addons->isNotEmpty())
                    <div class="mt-4 pt-3 border-top">
                        <h5 class="mb-1" style="font-family:'Cormorant Garamond',serif;font-size:1.3rem;">Enhance your stay</h5>
                        <p class="text-muted small mb-3">Add drinks packages, food hampers, or experiences to your booking.</p>
                        <div class="row g-3">
                            @foreach ($addons as $addon)
                                <div class="col-sm-6">
                                    <label class="ch-addon-option d-flex gap-3 p-3 rounded" for="addon_{{ $addon->id }}">
                                        <input type="checkbox" name="addon_ids[]" value="{{ $addon->id }}" id="addon_{{ $addon->id }}" class="form-check-input mt-1 addon-check" data-price="{{ $addon->price }}">
                                        <div class="flex-grow-1">
                                            <strong class="d-block" style="font-size:0.92rem;">{{ $addon->name }}</strong>
                                            <span class="text-muted small">{{ $addon->description ? \Illuminate\Support\Str::limit($addon->description, 60) : '' }}</span>
                                            <div class="mt-1 fw-semibold" style="color:var(--ch-forest);font-size:0.88rem;">
                                                £{{ number_format($addon->price, 2) }}
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

                <button class="btn btn-ch-book mt-4">Continue to payment</button>
            </form>
        </div>
        <div class="col-lg-5">
            <div class="ch-booking-card">
                <h2>{{ $room->name }}</h2>
                <p class="ch-suite-meta mb-1">{{ $checkIn->format('d M Y') }} → {{ $checkOut->format('d M Y') }}</p>
                <p class="ch-suite-meta">{{ $quote['nights'] }} night(s) · {{ $guests }} guest(s)</p>
                <hr>
                <div class="d-flex justify-content-between"><span>Stay</span><span>£{{ number_format($quote['base_amount'], 2) }}</span></div>
                @if ($quote['fees_amount'] > 0)
                    <div class="d-flex justify-content-between"><span>Cleaning fee</span><span>£{{ number_format($quote['fees_amount'], 2) }}</span></div>
                @endif
                <div id="addonsSummary"></div>
                @if (! empty($quote['damage_deposit']) && $quote['damage_deposit'] > 0)
                    <div class="d-flex justify-content-between"><span>Damage deposit (refunded after stay)</span><span>£{{ number_format($quote['damage_deposit'], 2) }}</span></div>
                @endif
                <div class="d-flex justify-content-between"><span>Taxes</span><span>£{{ number_format($quote['tax_amount'], 2) }}</span></div>
                <div class="d-flex justify-content-between ch-price mt-3"><span>Total</span><span id="totalDisplay">£{{ number_format($quote['total'], 2) }}</span></div>
                <input type="hidden" id="baseTotal" value="{{ $quote['total'] }}">

            </div>

            <div class="ch-booking-card mt-3">
                <h6 class="mb-2">House rules</h6>
                <ul class="small text-muted mb-0" style="padding-left:1.2rem;">
                    <li>Check-in from 3:00 PM · Check-out by 12:00 PM</li>
                    <li>Minimum stay: {{ \App\Models\Setting::getValue('min_stay_nights', 2) }} nights ({{ \App\Models\Setting::getValue('min_stay_bank_holiday_nights', 3) }} on bank holiday weekends)</li>
                    <li>Maximum guests: {{ \App\Models\Setting::getValue('max_adults', 12) }} adults, {{ \App\Models\Setting::getValue('max_infants', 2) }} infants (under 6), {{ \App\Models\Setting::getValue('max_cots', 2) }} cots</li>
                    <li>No pets allowed</li>
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

    function recalc() {
        let addonTotal = 0;
        let html = '';
        checks.forEach(function (cb) {
            if (cb.checked) {
                const price = parseFloat(cb.dataset.price);
                addonTotal += price;
                const name = cb.closest('label').querySelector('strong').textContent;
                html += '<div class="d-flex justify-content-between"><span>' + name + '</span><span>£' + price.toFixed(2) + '</span></div>';
            }
        });
        summaryEl.innerHTML = html;
        totalEl.textContent = '£' + (baseTotal + addonTotal).toFixed(2);
    }

    checks.forEach(function (cb) {
        cb.addEventListener('change', recalc);
    });
});
</script>
@endpush
@endsection
