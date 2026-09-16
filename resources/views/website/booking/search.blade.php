@extends('layouts.website.app')
@section('title', 'Availability')
@section('description', 'Check availability and rates for Corner House, Braunston, and book your stay direct.')
@section('content')
<section>
    <div class="section" style="padding-bottom:0">
        <div class="wrap">
            <p class="kicker">Bookings</p>
            <h1>Find a stay</h1>
            <p class="lede">Choose dates. We will show what is free, with the house rate.</p>
            <p class="small">Minimum stay: 2 nights (3 on bank holidays and seasonal events).</p>
        </div>
    </div>

    <div class="section">
        <div class="wrap">
            @if ($availableProperties->count() > 1)
                <div class="tabs">
                    @foreach ($availableProperties as $option)
                        <a class="tab"
                           @if ((int) $property?->id === (int) $option->id) aria-selected="true" @else aria-selected="false" @endif
                           href="{{ route('booking.search', array_filter(['property_id' => $option->getRouteKey(), 'check_in' => $checkIn, 'check_out' => $checkOut, 'guests' => $guests])) }}">
                            {{ $option->name }}
                        </a>
                    @endforeach
                </div>
            @endif

            <form method="GET" class="book-search">
                @if ($property)
                    <input type="hidden" name="property_id" value="{{ $property->getRouteKey() }}">
                @endif
                <div class="book-search-fields">
                    <label>Arrive
                        <input type="date" name="check_in" value="{{ $checkIn }}" required>
                    </label>
                    <label>Depart
                        <input type="date" name="check_out" value="{{ $checkOut }}" required>
                    </label>
                    <label>Guests
                        <input type="number" name="guests" min="1" value="{{ $guests }}">
                    </label>
                    <button class="btn btn-primary" type="submit">Search</button>
                </div>
            </form>

            <div class="stays">
                @forelse ($rooms as $room)
                    @php
                        $images = $room->images->sortBy('sort_order')->values();
                        $hero = $images->first();
                    @endphp
                    <article class="stay">
                        <div class="photo">@if($hero)<img src="{{ asset('storage/'.$hero->path) }}" alt="{{ $hero->alt ?: $room->name }}" loading="lazy">@endif</div>
                        <div>
                            <p class="eyebrow">{{ $availableProperties->count() > 1 && $room->property ? $room->property->name : 'Direct rate' }}</p>
                            <h3>{{ $room->property?->name ?? 'Whole house' }}</h3>
                            <p class="meta">Whole house &middot; Sleeps {{ $room->house_capacity }} &middot; {{ $room->quote['nights'] }} night(s)</p>
                            <p class="price">&pound;{{ number_format($room->quote['total'], 2) }}</p>
                            <p class="note">Instant direct booking for the whole house, with our 10% direct-booking discount applied.</p>
                            <a class="btn btn-primary" href="{{ route('booking.details', ['room' => $room, 'check_in' => $checkIn, 'check_out' => $checkOut, 'guests' => $guests]) }}">Book &amp; Checkout</a>
                        </div>
                    </article>
                @empty
                    @if ($checkIn)
                        <p class="small" style="margin-top:.5rem">No rooms available for those dates.</p>
                    @else
                        <p class="small" style="margin-top:.5rem">Choose dates to see availability and prices.</p>
                    @endif
                @endforelse
            </div>
        </div>
    </div>
</section>
@endsection