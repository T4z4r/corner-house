@extends('layouts.website.app')
@section('title', 'Guest details')
@section('content')
@include('website._page-hero', ['kicker' => 'Checkout', 'title' => 'Your details'])
<div class="container ch-section">
    <div class="row g-5">
        <div class="col-lg-7">
            <form method="POST" action="{{ route('booking.pay') }}" class="ch-form-card">
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
                @if (! empty($quote['damage_deposit']) && $quote['damage_deposit'] > 0)
                    <div class="d-flex justify-content-between"><span>Damage deposit (refunded after stay)</span><span>£{{ number_format($quote['damage_deposit'], 2) }}</span></div>
                @endif
                <div class="d-flex justify-content-between"><span>Taxes</span><span>£{{ number_format($quote['tax_amount'], 2) }}</span></div>
                <div class="d-flex justify-content-between ch-price mt-3"><span>Total</span><span>£{{ number_format($quote['total'], 2) }}</span></div>
                <p class="small text-muted mt-3 mb-0">The final amount is recalculated on the server before payment.</p>
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
@endsection
