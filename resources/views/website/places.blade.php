@extends('layouts.website.app')
@section('title', 'Places of Interest')
@section('content')
@include('website._page-hero', ['kicker' => 'Explore', 'title' => 'Places of Interest', 'subtitle' => 'Explore the surrounding area.'])

<section class="ch-section">
    <div class="container">
        @php
            $sorted = $places->sortBy('distance');
        @endphp
        @forelse ($sorted as $place)
            @if ($loop->first || $loop->iteration % 3 === 1)
                <div class="row g-4 mb-4">
            @endif
                    <div class="col-md-4">
                        <div class="ch-suite-card">
                            <div class="ch-suite-card-body">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="badge bg-secondary">{{ ucfirst($place->category) }}</span>
                                    @if ($place->distance)
                                        <span class="badge bg-primary">{{ $place->distance }}</span>
                                    @endif
                                </div>
                                <h3 class="ch-suite-card-title">{{ $place->name }}</h3>
                                @if ($place->description)
                                    <p class="ch-suite-card-text">{{ Str::limit($place->description, 120) }}</p>
                                @endif
                                @if ($place->website)
                                    <a class="ch-text-link" href="{{ $place->website }}" target="_blank" rel="noopener">
                                        Visit website <i class="bi bi-arrow-right"></i>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
            @if ($loop->last || $loop->iteration % 3 === 0)
                </div>
            @endif
        @empty
            <p class="text-muted">Local places of interest will be listed here once they are added.</p>
        @endforelse
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
