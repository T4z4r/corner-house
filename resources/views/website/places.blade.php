@extends('layouts.website.app')
@section('title', 'Places of Interest')
@section('content')
@include('website._page-hero', ['kicker' => 'Explore', 'title' => 'Places of Interest', 'subtitle' => 'Explore the surrounding area and discover what Northamptonshire has to offer'])

<section class="ch-section">
    <div class="container">
        @php
            $sorted = $places->sortBy('distance');
        @endphp
        <div class="row g-4">
            @forelse ($sorted as $place)
                <div class="col-md-4">
                    <div class="ch-info-card" data-bs-toggle="modal" data-bs-target="#placeModal{{ $place->id }}" role="button" tabindex="0">
                        <div class="ch-info-card-header">
                            <span class="ch-info-card-badge">{{ ucfirst($place->category) }}</span>
                            @if ($place->distance)
                                <span class="ch-info-card-distance">{{ $place->distance }}</span>
                            @endif
                        </div>
                        <h3 class="ch-info-card-title">{{ $place->name }}</h3>
                        @if ($place->description)
                            <p class="ch-info-card-text">{{ Str::limit($place->description, 100) }}</p>
                        @endif
                        <span class="ch-info-card-cta">View details <i class="bi bi-arrow-right"></i></span>
                    </div>
                </div>

                <div class="modal fade" id="placeModal{{ $place->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content ch-modal">
                            <div class="modal-header ch-modal-header">
                                <div>
                                    <span class="ch-info-card-badge mb-2">{{ ucfirst($place->category) }}</span>
                                    <h3 class="ch-modal-title">{{ $place->name }}</h3>
                                    @if ($place->distance)
                                        <div class="ch-modal-distance">{{ $place->distance }} from property</div>
                                    @endif
                                </div>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body ch-modal-body">
                                @if ($place->description)
                                    <p>{{ $place->description }}</p>
                                @endif
                                @if ($place->address)
                                    <div class="ch-modal-detail">
                                        <i class="bi bi-geo-alt"></i>
                                        <span>{{ $place->address }}</span>
                                    </div>
                                @endif
                                @if ($place->website)
                                    <div class="ch-modal-detail">
                                        <i class="bi bi-globe"></i>
                                        <a href="{{ $place->website }}" target="_blank" rel="noopener">Visit website <i class="bi bi-arrow-up-right"></i></a>
                                    </div>
                                @endif
                                @if ($place->phone)
                                    <div class="ch-modal-detail">
                                        <i class="bi bi-telephone"></i>
                                        <a href="tel:{{ $place->phone }}">{{ $place->phone }}</a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <p class="text-muted">Local places of interest will be listed here once they are added.</p>
                </div>
            @endforelse
        </div>
    </div>
</section>

<section class="ch-editorial-cta">
    <div class="container">
        <div class="ch-editorial-panel">
            <p class="ch-kicker">Need more?</p>
            <h2>Let us help you plan your days out.</h2>
            <a class="btn btn-ch-book" href="{{ route('contact') }}">Get in touch</a>
        </div>
    </div>
</section>
@endsection
