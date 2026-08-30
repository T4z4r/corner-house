@extends('layouts.website.app')
@section('title', 'Amenities')
@section('content')
@include('website._page-hero', ['kicker' => 'Comforts', 'title' => 'Amenities', 'subtitle' => 'What is waiting for you at the house.'])

<section class="ch-section">
    <div class="container">
        @php
            $grouped = $amenities->where('is_active')->groupBy(fn ($a) => $a->category ?: 'General');
        @endphp
        @forelse ($grouped as $category => $items)
            <div class="ch-amenity-group mb-5">
                <h2 class="ch-section-title mb-3">{{ $category }}</h2>
                <div class="row g-3">
                    @foreach ($items as $amenity)
                        <div class="col-md-4">
                            <div class="ch-amenity">
                                @if ($amenity->icon)
                                    <i class="bi {{ $amenity->icon }} ch-amenity-icon"></i>
                                @endif
                                <strong>{{ $amenity->name }}</strong>
                                @if ($amenity->description)
                                    <p class="small mt-1 mb-0">{{ $amenity->description }}</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <p class="text-muted">Amenities will be listed here once they are added.</p>
        @endforelse
    </div>
</section>

<section class="ch-editorial-cta">
    <div class="container">
        <div class="ch-editorial-panel">
            <p class="ch-kicker">Questions</p>
            <h2>Need something specific for your stay?</h2>
            <a class="btn btn-ch-book" href="{{ route('contact') }}">Get in touch</a>
        </div>
    </div>
</section>
@endsection
