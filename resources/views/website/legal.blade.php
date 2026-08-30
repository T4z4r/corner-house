@extends('layouts.website.app')
@section('title', $title)
@section('content')
@include('website._page-hero', ['kicker' => 'House terms', 'title' => $heading])
<div class="container ch-section">
    <p class="ch-prose">Please contact us if you need a copy of the full policy for your stay. Booking terms are confirmed at checkout.</p>
    @if ($property)
        @foreach ($property->policies as $policy)
            <h2 class="ch-section-title mt-4">{{ $policy->title ?? $policy->name ?? 'Policy' }}</h2>
            <p class="ch-prose">{{ $policy->content ?? $policy->body ?? '' }}</p>
        @endforeach
    @endif
</div>
@endsection
