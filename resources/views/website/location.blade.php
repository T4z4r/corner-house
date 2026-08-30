@extends('layouts.website.app')
@section('title', 'Location')
@section('content')
@include('website._page-hero', ['kicker' => 'Arrive', 'title' => 'Location', 'subtitle' => 'Corner House sits in the heart of Northamptonshire.'])

<section class="ch-section">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-5">
                <h2 class="ch-section-title mb-3">Find us</h2>
                @if ($property)
                    <div class="ch-location-card">
                        <div class="ch-location-detail">
                            <i class="bi bi-geo-alt ch-location-icon"></i>
                            <div>
                                <div class="ch-location-label">Address</div>
                                <div>{{ $property->address_line_1 }}</div>
                                @if ($property->address_line_2)
                                    <div>{{ $property->address_line_2 }}</div>
                                @endif
                                <div>{{ $property->city }} {{ $property->postcode }}</div>
                                <div>{{ $property->country }}</div>
                            </div>
                        </div>
                        @if ($property->phone)
                            <div class="ch-location-detail mt-3">
                                <i class="bi bi-telephone ch-location-icon"></i>
                                <div>
                                    <div class="ch-location-label">Phone</div>
                                    <div>{{ $property->phone }}</div>
                                </div>
                            </div>
                        @endif
                    </div>
                    <a class="btn btn-ch-book mt-4" href="https://www.google.com/maps/search/?api=1&address={{ urlencode($property->address_line_1.', '.$property->city.' '.$property->postcode) }}" target="_blank" rel="noopener">Open in Google Maps</a>
                @else
                    <p>Location details will be published with the property.</p>
                @endif
            </div>
            <div class="col-lg-7">
                <div class="ch-location-map-placeholder" aria-label="Map">
                    <div class="ch-location-map-inner">
                        <i class="bi bi-map fs-1 mb-2"></i>
                        <p>Interactive map coming soon.</p>
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
            <h2>Know what to expect before you arrive.</h2>
            <a class="btn btn-ch-book" href="{{ route('faq') }}">Read the FAQ</a>
        </div>
    </div>
</section>
@endsection
