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
                @php $image = $room->images->first(); @endphp
                <div class="col-md-6">
                    <article class="ch-suite-card">
                        @if ($image)
                            <div class="ch-suite-media">
                                <img src="{{ asset('storage/'.$image->path) }}" alt="{{ $image->alt ?: $room->name }}">
                            </div>
                        @endif
                        <div class="ch-suite-body">
                            <div class="ch-suite-eyebrow">{{ ucfirst($room->type ?? 'Room') }}</div>
                            <h3>{{ $room->name }}</h3>
                            <p class="ch-suite-meta">Sleeps {{ $room->capacity }} · from £{{ number_format($room->base_rate, 2) }} / night</p>
                            <p>{{ \Illuminate\Support\Str::limit($room->description, 160) }}</p>
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

<section class="ch-editorial-cta">
    <div class="container">
        <div class="ch-editorial-panel">
            <p class="ch-kicker">Plan your stay</p>
            <h2>See what is available for your dates.</h2>
            <a class="btn btn-ch-book" href="{{ route('booking.search') }}">Check availability</a>
        </div>
    </div>
</section>
@endsection
