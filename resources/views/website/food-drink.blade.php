@extends('layouts.website.app')
@section('title', 'Food & Drink')
@section('content')
@include('website._page-hero', ['kicker' => 'Enjoy', 'title' => 'Food & Drink', 'subtitle' => 'Local favourites and drinks packages for your stay'])

<section class="ch-section">
    <div class="container">
        <h2 class="ch-section-title mb-4">Local Establishments</h2>
        @php
            $featured = $establishments->where('is_active')->where('is_featured')->sortBy('sort_order');
            $regular = $establishments->where('is_active')->where('is_featured', false)->sortBy('sort_order');
            $allEstablishments = $featured->concat($regular);
        @endphp
        @forelse ($allEstablishments->chunk(3) as $chunk)
            <div class="row g-4 mb-4">
                @foreach ($chunk as $e)
                    <div class="col-md-4">
                        <div class="ch-info-card" data-bs-toggle="modal" data-bs-target="#foodModal{{ $e->id }}" role="button" tabindex="0">
                            @if ($e->image)
                                <div class="ch-info-card-img">
                                    <img src="{{ asset('storage/'.$e->image) }}" alt="{{ $e->name }}">
                                </div>
                            @endif
                            <div class="ch-info-card-content">
                                <span class="ch-info-card-badge">{{ ucfirst($e->category) }}</span>
                                @if ($e->is_featured)
                                    <span class="ch-info-card-featured">Featured</span>
                                @endif
                                <h3 class="ch-info-card-title">{{ $e->name }}</h3>
                                <p class="ch-info-card-text">{{ Str::limit($e->description, 100) }}</p>
                                @if ($e->address)
                                    <div class="ch-info-card-meta">
                                        <i class="bi bi-geo-alt"></i> {{ $e->address }}
                                    </div>
                                @endif
                                <span class="ch-info-card-cta">View details <i class="bi bi-arrow-right"></i></span>
                            </div>
                        </div>
                    </div>

                    <div class="modal fade" id="foodModal{{ $e->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content ch-modal">
                                @if ($e->image)
                                    <div class="ch-modal-img">
                                        <img src="{{ asset('storage/'.$e->image) }}" alt="{{ $e->name }}">
                                    </div>
                                @endif
                                <div class="modal-header ch-modal-header">
                                    <div>
                                        <span class="ch-info-card-badge mb-2">{{ ucfirst($e->category) }}</span>
                                        <h3 class="ch-modal-title">{{ $e->name }}</h3>
                                    </div>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body ch-modal-body">
                                    @if ($e->description)
                                        <p>{{ $e->description }}</p>
                                    @endif
                                    @if ($e->address)
                                        <div class="ch-modal-detail">
                                            <i class="bi bi-geo-alt"></i>
                                            <span>{{ $e->address }}</span>
                                        </div>
                                    @endif
                                    @if ($e->phone)
                                        <div class="ch-modal-detail">
                                            <i class="bi bi-telephone"></i>
                                            <a href="tel:{{ $e->phone }}">{{ $e->phone }}</a>
                                        </div>
                                    @endif
                                    @if ($e->website)
                                        <div class="ch-modal-detail">
                                            <i class="bi bi-globe"></i>
                                            <a href="{{ $e->website }}" target="_blank" rel="noopener">Visit website <i class="bi bi-arrow-up-right"></i></a>
                                        </div>
                                    @endif
                                </div>
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

<section class="ch-section ch-section-alt">
    <div class="container">
        <h2 class="ch-section-title mb-4">Drinks Packages & Add-Ons</h2>
        <div class="row g-4">
            @forelse ($addons->where('is_active')->sortBy('sort_order') as $addon)
                <div class="col-md-6 col-lg-3">
                    <div class="ch-info-card ch-info-card--accent" data-bs-toggle="modal" data-bs-target="#addonModal{{ $addon->id }}" role="button" tabindex="0">
                        <h3 class="ch-info-card-title">{{ $addon->name }}</h3>
                        <p class="ch-info-card-text">{{ Str::limit($addon->description, 80) }}</p>
                        <div class="ch-info-card-price">
                            <strong>£{{ number_format($addon->price, 2) }}</strong>
                            @if ($addon->unit)
                                <span>/ {{ $addon->unit }}</span>
                            @endif
                        </div>
                        <span class="ch-info-card-cta">View details <i class="bi bi-arrow-right"></i></span>
                    </div>
                </div>

                <div class="modal fade" id="addonModal{{ $addon->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content ch-modal">
                            <div class="modal-header ch-modal-header">
                                <div>
                                    <h3 class="ch-modal-title">{{ $addon->name }}</h3>
                                    <div class="ch-info-card-price mt-1">
                                        <strong>£{{ number_format($addon->price, 2) }}</strong>
                                        @if ($addon->unit)
                                            <span>/ {{ $addon->unit }}</span>
                                        @endif
                                    </div>
                                </div>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body ch-modal-body">
                                @if ($addon->description)
                                    <p>{{ $addon->description }}</p>
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
