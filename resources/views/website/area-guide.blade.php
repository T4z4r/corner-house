@extends('layouts.website.app')
@section('title', 'Area Guide')
@section('content')
@include('website._page-hero', ['kicker' => 'Explore', 'title' => 'Area Guide', 'subtitle' => 'A quick view of the weather, local events, and nearby highlights.'])

<section class="ch-section">
    <div class="container">
        <div class="d-flex flex-wrap align-items-center gap-2 mb-4">
            <span class="text-uppercase text-muted small fw-semibold">Events:</span>
            <a class="btn btn-sm {{ $selectedPeriod === 'week' ? 'btn-ch-primary' : 'btn-outline-secondary' }}" href="{{ route('area-guide', ['period' => 'week', 'date' => $anchorDate->toDateString()]) }}">This week</a>
            <a class="btn btn-sm {{ $selectedPeriod === 'month' ? 'btn-ch-primary' : 'btn-outline-secondary' }}" href="{{ route('area-guide', ['period' => 'month', 'date' => $anchorDate->toDateString()]) }}">This month</a>
            <span class="text-muted small ms-auto">{{ $windowLabel }}</span>
        </div>
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <p class="text-uppercase text-muted small mb-2">Weather forecast</p>
                        <h3 class="h5 mb-3">Near Corner House</h3>
                        @if (($weatherForecast['days'] ?? []) !== [])
                            @forelse ($weatherForecast['days'] as $day)
                                <div class="d-flex justify-content-between align-items-start border-bottom py-2">
                                    <div>
                                        <div class="fw-semibold">{{ $day['label'] }}</div>
                                        <div class="text-muted small">{{ $day['summary'] }}</div>
                                    </div>
                                    <div class="text-end small">
                                        @if ($day['high_c'] !== null && $day['low_c'] !== null)
                                            <div>{{ number_format($day['high_c']) }}° / {{ number_format($day['low_c']) }}°</div>
                                        @endif
                                        @if ($day['rain_probability'] !== null)
                                            <div class="text-muted">{{ $day['rain_probability'] }}% rain</div>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <p class="text-muted mb-0">Weather details will appear once the property coordinates are set.</p>
                            @endforelse
                        @else
                            <p class="text-muted mb-0">Weather details will appear once the property coordinates are set.</p>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <p class="text-uppercase text-muted small mb-2">Nearby events</p>
                        <h3 class="h5 mb-3">What is happening locally</h3>
                        @forelse ($localEvents as $event)
                            <div class="border-bottom py-2">
                                <div class="fw-semibold">{{ $event['title'] }}</div>
                                <div class="text-muted small">{{ $event['category'] }}</div>
                                @if ($event['starts_at'])
                                    <div class="text-muted small">{{ $event['starts_at'] }}</div>
                                @endif
                                <div class="small">{{ $event['summary'] }}</div>
                            </div>
                        @empty
                            <p class="text-muted mb-0">Local event highlights will appear here when they are published.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>

<section class="ch-editorial-cta">
    <div class="container">
        <div class="ch-editorial-panel">
            <p class="ch-kicker">Plan ahead</p>
            <h2>Check the area guide before you book or arrive.</h2>
            <a class="btn btn-ch-book" href="{{ route('booking.search') }}">Check availability</a>
        </div>
    </div>
</section>
@endsection
