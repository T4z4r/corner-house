@extends('layouts.website.app')

@section('title', 'Stay with us')

@section('content')
<section class="ch-hero">
    <div class="ch-hero-overlay"></div>
    <div class="container ch-hero-inner">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <p class="ch-kicker">A private stay · Book direct</p>
                <h1>{{ $property?->name ?? $propertyName }}</h1>
                <p class="ch-hero-lead">{{ $property?->short_description ?? 'A calm, independently hosted house. Fewer platforms, a warmer welcome, and the best rate when you book with us.' }}</p>
                <div class="ch-hero-actions">
                    <a class="btn btn-ch-book" href="{{ route('booking.search') }}">Check availability</a>
                    <a class="ch-hero-link" href="{{ route('property') }}">Explore the house</a>
                </div>
                <div class="ch-hero-metrics">
                    <div><span>{{ $property?->capacity ?? '4' }}</span><small>Guests</small></div>
                    <div><span>{{ $property?->bedrooms ?? '2' }}</span><small>Bedrooms</small></div>
                    <div><span>Direct</span><small>Best house rate</small></div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="ch-hero-gallery" aria-hidden="true">
                    <div class="ch-hero-photo ch-hero-photo-main"></div>
                    <div class="ch-hero-photo ch-hero-photo-small"></div>
                    <div class="ch-hero-stamp">Boutique stay<br>Northamptonshire</div>
                </div>
                <div class="ch-booking-card">
                    <h2>Reserve your dates</h2>
                    <p class="ch-booking-note">Live availability. Final price is always calculated by the house.</p>
                    <form method="GET" action="{{ route('booking.search') }}" class="row g-3">
                        <div class="col-6">
                            <label class="form-label">Arrive</label>
                            <input type="date" name="check_in" class="form-control" required min="{{ now()->toDateString() }}">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Depart</label>
                            <input type="date" name="check_out" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Guests</label>
                            <input type="number" name="guests" class="form-control" min="1" value="2">
                        </div>
                        <div class="col-12">
                            <button class="btn btn-ch-book w-100">Search stays</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="ch-section">
    <div class="container">
        <div class="ch-section-intro ch-section-intro-split">
            <div>
                <p class="ch-kicker">Suites</p>
                <h2>Rooms prepared with care</h2>
            </div>
            <p>Every room is presented with a quiet residential feel: considered amenities, simple arrival, and pricing handled directly by Corner House.</p>
        </div>
        <div class="row g-4">
            @forelse ($rooms as $room)
                @php $image = $room->images->first(); @endphp
                <div class="col-md-4">
                    <article class="ch-suite-card">
                        <div class="ch-suite-media">
                            @if ($image)
                                <img src="{{ asset('storage/'.$image->path) }}" alt="{{ $image->alt ?: $room->name }}">
                            @endif
                        </div>
                        <div class="ch-suite-body">
                            <div class="ch-suite-eyebrow">Private room</div>
                            <h3>{{ $room->name }}</h3>
                            <p class="ch-suite-meta">Sleeps {{ $room->capacity }} · from £{{ number_format($room->base_rate, 2) }} / night</p>
                            <p>{{ \Illuminate\Support\Str::limit($room->description, 140) }}</p>
                            <a class="ch-text-link" href="{{ route('booking.search') }}">Check dates</a>
                        </div>
                    </article>
                </div>
            @empty
                <div class="col-12">
                    <p class="text-muted">Rooms will appear here once they are published.</p>
                </div>
            @endforelse
        </div>
    </div>
</section>

<section class="ch-section ch-section-alt">
    <div class="container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-5">
                <p class="ch-kicker">Why book here</p>
                <h2>The quieter way to stay</h2>
                <p class="ch-prose">Direct booking means we can look after you properly — from arrival notes to a rate that is not inflated by commission.</p>
            </div>
            <div class="col-lg-7">
                <div class="row g-3">
                    <div class="col-md-4"><div class="ch-feature"><span>01</span><strong>Best available rate</strong><p>No OTA markup when you reserve with the house.</p></div></div>
                    <div class="col-md-4"><div class="ch-feature"><span>02</span><strong>Hosted, not listed</strong><p>Clear house rules, local notes, and a person at the other end.</p></div></div>
                    <div class="col-md-4"><div class="ch-feature"><span>03</span><strong>Secure checkout</strong><p>Pay by Stripe. Your reservation is confirmed only after payment is verified.</p></div></div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="ch-editorial-cta">
    <div class="container">
        <div class="ch-editorial-panel">
            <p class="ch-kicker">Ready when you are</p>
            <h2>Secure your dates without the platform noise.</h2>
            <a class="btn btn-ch-book" href="{{ route('booking.search') }}">Book direct</a>
        </div>
    </div>
</section>
@endsection
