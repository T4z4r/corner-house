@extends('layouts.website.app')
@section('title', 'Availability')
@section('content')
@include('website._page-hero', ['kicker' => 'Bookings', 'title' => 'Find a stay', 'subtitle' => 'Choose dates. We will show what is free, with the house rate.'])
<div class="container ch-section">
    <form method="GET" class="ch-booking-card ch-booking-bar ch-booking-bar-premium mb-5">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Arrive</label>
                <input type="date" name="check_in" class="form-control" value="{{ $checkIn }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Depart</label>
                <input type="date" name="check_out" class="form-control" value="{{ $checkOut }}" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Guests</label>
                <input type="number" name="guests" class="form-control" min="1" value="{{ $guests }}">
            </div>
            <div class="col-md-4">
                <button class="btn btn-ch-book w-100">Search</button>
            </div>
        </div>
    </form>

    <div class="row g-4">
        @forelse ($rooms as $room)
            <div class="col-md-6">
                <article class="ch-suite-card ch-result-card">
                    <div class="ch-result-ribbon">Available</div>
                    <div class="ch-suite-body">
                        <div class="ch-suite-eyebrow">Direct rate</div>
                        <h3>{{ $room->name }}</h3>
                        <p class="ch-suite-meta">Sleeps {{ $room->capacity }} · {{ $room->quote['nights'] }} night(s)</p>
                        <p class="ch-price">£{{ number_format($room->quote['total'], 2) }}</p>
                        <p class="text-muted small">No browser-submitted price is trusted. This total was calculated by the booking engine.</p>
                        <a class="btn btn-ch-book" href="{{ route('booking.details', ['room' => $room, 'check_in' => $checkIn, 'check_out' => $checkOut, 'guests' => $guests]) }}">Continue</a>
                    </div>
                </article>
            </div>
        @empty
            @if ($checkIn)
                <p class="text-muted">No rooms available for those dates.</p>
            @else
                <p class="text-muted">Choose dates to see availability and prices.</p>
            @endif
        @endforelse
    </div>
</div>
@endsection
