@extends('layouts.website.app')
@section('title', 'The property')
@section('content')
@include('website._page-hero', ['kicker' => 'The house', 'title' => $property?->name ?? $propertyName, 'subtitle' => $property?->short_description ?? 'An independently hosted stay in Northamptonshire.'])

<section class="ch-section">
    <div class="container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6">
                <p class="ch-prose">{{ $property?->description ?? 'Corner House is a calm, privately hosted stay. Direct booking means a warmer welcome and the best available rate.' }}</p>
            </div>
            <div class="col-lg-6">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="ch-stat-card">
                            <div class="ch-stat-value">{{ $property?->bedrooms ?? '-' }}</div>
                            <div class="ch-stat-label">Bedrooms</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="ch-stat-card">
                            <div class="ch-stat-value">{{ $property?->bathrooms ?? '-' }}</div>
                            <div class="ch-stat-label">Bathrooms</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="ch-stat-card">
                            <div class="ch-stat-value">{{ $property?->capacity ?? '-' }}</div>
                            <div class="ch-stat-label">Guests</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="ch-stat-card">
                            <div class="ch-stat-value">{{ $rooms->count() }}</div>
                            <div class="ch-stat-label">Rooms</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="ch-section ch-section-alt">
    <div class="container">
        <div class="ch-section-intro">
            <p class="ch-kicker">Rooms</p>
            <h2>Choose where you will stay</h2>
        </div>
        <div class="row g-4">
            @forelse ($rooms as $room)
                @php
                    $images = $room->images->sortBy('sort_order')->values();
                    $hero = $images->first();
                    $thumbs = $images->slice(1, 3);
                @endphp
                <div class="col-md-6">
                    <article class="ch-suite-card">
                        @if ($images->isNotEmpty())
                            <div class="ch-suite-gallery">
                                <div class="ch-suite-gallery-hero">
                                    <img src="{{ asset('storage/'.$hero->path) }}" alt="{{ $hero->alt ?: $room->name }}">
                                </div>
                                @foreach ($thumbs as $thumb)
                                    <div class="ch-suite-gallery-thumb">
                                        <img src="{{ asset('storage/'.$thumb->path) }}" alt="{{ $thumb->alt ?: $room->name }}">
                                    </div>
                                @endforeach
                                @if ($images->count() > 4)
                                    <div class="ch-suite-gallery-count"><i class="bi bi-images me-1"></i>{{ $images->count() }}</div>
                                @endif
                            </div>
                        @else
                            <div class="ch-suite-no-image">
                                <i class="bi bi-house"></i>
                            </div>
                        @endif
                        <div class="ch-suite-body">
                            <div class="ch-suite-eyebrow">{{ ucfirst($room->type ?? 'Room') }}</div>
                            <h3>{{ $room->name }}</h3>
                            <p class="ch-suite-meta">Sleeps {{ $room->capacity }} · from £{{ number_format($room->base_rate, 2) }} / night</p>
                            <p>{{ \Illuminate\Support\Str::limit($room->description, 160) }}</p>
                            <a class="ch-text-link" href="{{ route('property.room', $room) }}">View details</a>
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

<section class="ch-section">
    <div class="container">
        <div class="ch-section-intro">
            <p class="ch-kicker">Know before you go</p>
            <h2>House rules</h2>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="ch-info-card">
                    <div class="ch-info-card-content">
                        <span class="ch-info-card-badge">Check-in &amp; check-out</span>
                        <h3 class="ch-info-card-title">Arrivals &amp; departures</h3>
                        <p class="ch-info-card-text">Check-in from 3:00 PM. Check-out by 12:00 PM (noon). Please let us know your estimated arrival time.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="ch-info-card">
                    <div class="ch-info-card-content">
                        <span class="ch-info-card-badge">Minimum stay</span>
                        <h3 class="ch-info-card-title">Length of stay</h3>
                        <p class="ch-info-card-text">2 nights minimum. 3 nights required on bank holiday weekends. 48 hours advance booking notice.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="ch-info-card">
                    <div class="ch-info-card-content">
                        <span class="ch-info-card-badge">Occupancy</span>
                        <h3 class="ch-info-card-title">Guest limits</h3>
                        <p class="ch-info-card-text">Maximum 12 adults, 2 infants (under 6), and 2 cots. No pets. No smoking indoors.</p>
                    </div>
                </div>
            </div>
        </div>
        @if ($property?->custom_rules)
            <div class="mt-4 p-4 rounded" style="background:#fbf8f1;">
                <h5 class="mb-3">Full house rules</h5>
                <div class="small" style="white-space:pre-line; color:#4a4540;">{{ $property->custom_rules }}</div>
            </div>
        @endif
    </div>
</section>

<section class="ch-editorial-cta">
    <div class="container">
        <div class="ch-editorial-panel">
            <p class="ch-kicker">Plan your stay</p>
            <h2>See what is available for your dates.</h2>
            <a class="btn btn-ch-book" href="{{ route('booking.search') }}">Check availability</a>
        </div>
    </div>
</section>

@php
    $airbnbUrl = \App\Models\Setting::getValue('platform_airbnb_url');
    $bookingUrl = \App\Models\Setting::getValue('platform_booking_url');
    $vrboUrl = \App\Models\Setting::getValue('platform_vrbo_url');
    $hasPlatforms = $airbnbUrl || $bookingUrl || $vrboUrl;
    $discount = \App\Models\Setting::getValue('direct_booking_discount', 10);
@endphp

@if ($hasPlatforms)
<section class="ch-section">
    <div class="container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6">
                <p class="ch-kicker">Book direct</p>
                <h2>Save {{ $discount }}% when you book directly</h2>
                <p class="ch-prose">Our best rates are available when you book through this website. You can also find us on these platforms:</p>
            </div>
            <div class="col-lg-6">
                <div class="d-flex flex-column gap-3">
                    @if ($airbnbUrl)
                        <a href="{{ $airbnbUrl }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary btn-lg">
                            <i class="bi bi-house-heart me-2"></i>Book on Airbnb
                        </a>
                    @endif
                    @if ($bookingUrl)
                        <a href="{{ $bookingUrl }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary btn-lg">
                            <i class="bi bi-building me-2"></i>Book on Booking.com
                        </a>
                    @endif
                    @if ($vrboUrl)
                        <a href="{{ $vrboUrl }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary btn-lg">
                            <i class="bi bi-house-door me-2"></i>Book on VRBO
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
@endif
@endsection
