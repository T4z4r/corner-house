@extends('layouts.website.app')
@section('title', 'Food & Drink')
@section('content')
@include('website._page-hero', ['kicker' => 'Enjoy', 'title' => 'Food & Drink', 'subtitle' => 'Local favourites and drinks packages'])

<section class="ch-section">
    <div class="container">
        <h2 class="ch-section-title mb-4">Local Establishments</h2>
        @php
            $featured = $establishments->where('is_active')->where('is_featured')->sortBy('sort_order');
            $regular = $establishments->where('is_active')->where('is_featured', false)->sortBy('sort_order');
        @endphp
        @php
            $allEstablishments = $featured->concat($regular);
        @endphp
        @forelse ($allEstablishments->chunk(3) as $chunk)
            <div class="row g-4 mb-4">
                @foreach ($chunk as $establishment)
                    <div class="col-md-4">
                        <div class="ch-suite-card">
                            <div class="ch-suite-card-body">
                                <span class="badge ch-badge mb-2">{{ ucfirst($establishment->category) }}</span>
                                <h3 class="ch-suite-card-title">{{ $establishment->name }}</h3>
                                <p class="ch-suite-card-text">{{ Str::limit($establishment->description, 120) }}</p>
                                @if ($establishment->address)
                                    <div class="ch-location-detail mb-2">
                                        <i class="bi bi-geo-alt ch-location-icon"></i>
                                        <span>{{ $establishment->address }}</span>
                                    </div>
                                @endif
                                @if ($establishment->phone)
                                    <div class="ch-location-detail mb-2">
                                        <i class="bi bi-telephone ch-location-icon"></i>
                                        <span>{{ $establishment->phone }}</span>
                                    </div>
                                @endif
                                @if ($establishment->website)
                                    <a class="ch-text-link" href="{{ $establishment->website }}" target="_blank" rel="noopener">Visit website <i class="bi bi-arrow-up-right"></i></a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @empty
            <p class="text-muted">Local establishments will be listed here once they are added.</p>
        @endforelse
    </div>
</section>

<section class="ch-section">
    <div class="container">
        <h2 class="ch-section-title mb-4">Drinks Packages & Add-Ons</h2>
        <div class="row g-4">
            @forelse ($addons->where('is_active')->sortBy('sort_order') as $addon)
                <div class="col-md-4">
                    <div class="ch-suite-card">
                        <div class="ch-suite-card-body">
                            <h3 class="ch-suite-card-title">{{ $addon->name }}</h3>
                            <p class="ch-suite-card-text">{{ $addon->description }}</p>
                            <div class="ch-suite-card-price">
                                <strong>£{{ number_format($addon->price, 2) }}</strong>
                                @if ($addon->unit)
                                    <span class="text-muted"> / {{ $addon->unit }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <p class="text-muted">Drinks packages and add-ons will appear here once they are added.</p>
                </div>
            @endforelse
        </div>
    </div>
</section>

<section class="ch-editorial-cta">
    <div class="container">
        <div class="ch-editorial-panel">
            <p class="ch-kicker">Plan ahead</p>
            <h2>Need something special for your stay?</h2>
            <a class="btn btn-ch-book" href="{{ route('contact') }}">Get in touch</a>
        </div>
    </div>
</section>
@endsection
