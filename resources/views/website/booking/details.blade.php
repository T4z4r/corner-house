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
                <div class="d-flex justify-content-between"><span>Taxes</span><span>£{{ number_format($quote['tax_amount'], 2) }}</span></div>
                <div class="d-flex justify-content-between ch-price mt-3"><span>Total</span><span>£{{ number_format($quote['total'], 2) }}</span></div>
                <p class="small text-muted mt-3 mb-0">The final amount is recalculated on the server before payment.</p>
            </div>
        </div>
    </div>
</div>
@endsection
