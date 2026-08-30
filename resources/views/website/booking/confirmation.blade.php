@extends('layouts.website.app')
@section('title', 'Booking confirmation')
@section('content')
@include('website._page-hero', ['kicker' => 'Thank you', 'title' => $reservation && $reservation->status === 'confirmed' ? 'Booking confirmed' : 'Your reservation'])
<div class="container ch-section">
    @if ($reservation)
        <div class="col-lg-7 mx-auto">
            <div class="ch-booking-card">
                <p>Reference <strong>{{ $reservation->reference }}</strong></p>
                <p>{{ $reservation->room?->name }} · {{ $reservation->check_in->format('d M Y') }} to {{ $reservation->check_out->format('d M Y') }}</p>
                <p class="ch-price">Total £{{ number_format($reservation->total_amount, 2) }} · {{ ucfirst($reservation->payment_status) }}</p>
                <p class="mb-0 small text-muted">A confirmation email will be sent when payment is verified.</p>
            </div>
        </div>
    @else
        <p>We could not find that booking. If you have just paid, please wait a moment and check your email.</p>
    @endif
</div>
@endsection
