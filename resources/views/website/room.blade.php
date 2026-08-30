@extends('layouts.website.app')

@section('title', $room->name)

@php
    $hero = $room->primaryImage();
    $images = $room->images->sortBy('sort_order')->values();
    $minStay = $room->min_stay ?: Setting::getValue('min_stay_nights', 2);
@endphp

@section('content')
@include('website._page-hero', ['kicker' => ucfirst($room->type ?? 'Room'), 'title' => $room->name, 'subtitle' => 'Sleeps '.$room->capacity.' · from £'.number_format($room->base_rate, 2).' / night'])

<section class="ch-section">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-7">
                @if ($hero)
                    <div class="rounded overflow-hidden mb-3" style="max-height:480px;">
                        <img src="{{ asset('storage/'.$hero->path) }}" alt="{{ $hero->alt ?: $room->name }}" class="w-100" style="object-fit:cover; max-height:480px;">
                    </div>
                @endif
                @if ($images->count() > 1)
                    <div class="row g-2">
                        @foreach ($images->slice(1, 4) as $img)
                            <div class="col-3">
                                <div class="rounded overflow-hidden" style="aspect-ratio:1/1;">
                                    <img src="{{ asset('storage/'.$img->path) }}" alt="{{ $img->alt ?: $room->name }}" class="w-100 h-100" style="object-fit:cover;">
                                </div>
                            </div>
                        @endforeach
                        @if ($images->count() > 5)
                            <div class="col-3">
                                <a href="{{ route('gallery') }}" class="d-block rounded overflow-hidden text-decoration-none" style="aspect-ratio:1/1; background:#1f6f43;">
                                    <div class="d-flex align-items-center justify-content-center h-100 text-white fw-semibold">
                                        +{{ $images->count() - 4 }} more
                                    </div>
                                </a>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
            <div class="col-lg-5">
                <div class="mb-4">
                    <h2>About this room</h2>
                    <p class="ch-prose">{{ $room->description }}</p>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-6">
                        <div class="ch-stat-card">
                            <div class="ch-stat-value">{{ $room->capacity }}</div>
                            <div class="ch-stat-label">Sleeps</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="ch-stat-card">
                            <div class="ch-stat-value">£{{ number_format($room->base_rate, 0) }}</div>
                            <div class="ch-stat-label">Per night</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="ch-stat-card">
                            <div class="ch-stat-value">{{ $minStay }}</div>
                            <div class="ch-stat-label">Min nights</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="ch-stat-card">
                            <div class="ch-stat-value">{{ ucfirst($room->type ?? 'Room') }}</div>
                            <div class="ch-stat-label">Type</div>
                        </div>
                    </div>
                </div>

                @if ($room->property?->amenities->isNotEmpty())
                    <div class="mb-4">
                        <h5>Property amenities</h5>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach ($room->property->amenities as $amenity)
                                <span class="badge bg-light text-dark border">{{ $amenity->name }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="d-grid gap-2">
                    <a class="btn btn-ch-book btn-lg" href="{{ route('booking.search', ['check_in' => now()->addDays(2)->format('Y-m-d'), 'check_out' => now()->addDays(4)->format('Y-m-d'), 'guests' => min($room->capacity, 2)]) }}">
                        Check availability
                    </a>
                    <a class="btn btn-outline-secondary" href="{{ route('property') }}">Back to all rooms</a>
                </div>
            </div>
        </div>
    </div>
</section>

@if ($room->property?->policies->isNotEmpty())
<section class="ch-section ch-section-alt">
    <div class="container">
        <div class="ch-section-intro">
            <p class="ch-kicker">Before you stay</p>
            <h2>Policies</h2>
        </div>
        <div class="row g-4">
            @foreach ($room->property->policies as $policy)
                <div class="col-md-4">
                    <div class="ch-info-card">
                        <div class="ch-info-card-content">
                            <span class="ch-info-card-badge">{{ $policy->category }}</span>
                            <h3 class="ch-info-card-title">{{ $policy->title }}</h3>
                            <p class="ch-info-card-text">{{ $policy->description }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

@if ($siblingRooms->isNotEmpty())
<section class="ch-section">
    <div class="container">
        <div class="ch-section-intro">
            <p class="ch-kicker">Other rooms</p>
            <h2>You might also like</h2>
        </div>
        <div class="row g-4">
            @foreach ($siblingRooms as $sibling)
                @php
                    $siblingHero = $sibling->primaryImage();
                @endphp
                <div class="col-md-4">
                    <article class="ch-suite-card">
                        @if ($siblingHero)
                            <div class="ch-suite-gallery">
                                <div class="ch-suite-gallery-hero">
                                    <img src="{{ asset('storage/'.$siblingHero->path) }}" alt="{{ $siblingHero->alt ?: $sibling->name }}">
                                </div>
                            </div>
                        @else
                            <div class="ch-suite-no-image"><i class="bi bi-house"></i></div>
                        @endif
                        <div class="ch-suite-body">
                            <div class="ch-suite-eyebrow">{{ ucfirst($sibling->type ?? 'Room') }}</div>
                            <h3>{{ $sibling->name }}</h3>
                            <p class="ch-suite-meta">Sleeps {{ $sibling->capacity }} · from £{{ number_format($sibling->base_rate, 2) }} / night</p>
                            <a class="ch-text-link" href="{{ route('property.room', $sibling) }}">View details</a>
                        </div>
                    </article>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

<section class="ch-editorial-cta">
    <div class="container">
        <div class="ch-editorial-panel">
            <p class="ch-kicker">Ready to book?</p>
            <h2>Check what is available for your dates.</h2>
            <a class="btn btn-ch-book" href="{{ route('booking.search') }}">Check availability</a>
        </div>
    </div>
</section>
@endsection
