@extends('layouts.website.app')
@section('title', 'About')
@section('content')
@include('website._page-hero', ['kicker' => 'Our story', 'title' => 'About '.($property?->name ?? $propertyName)])

<section class="ch-section">
    <div class="container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6">
                <p class="ch-prose">{{ $property?->description ?? 'Corner House is independently hosted, with direct booking and a focus on a calm stay.' }}</p>
                <p class="ch-prose mt-3">We believe a good stay starts with trust: clear pricing, honest descriptions, and a host who actually cares whether you slept well.</p>
            </div>
            <div class="col-lg-6">
                <div class="ch-about-card">
                    <div class="ch-about-card-inner">
                        <p class="ch-kicker">Our promise</p>
                        <h3>Hosted, not listed</h3>
                        <p class="ch-prose">No hidden fees, no inflated platform pricing, and no call centres. When you book direct, we look after you ourselves — from the first message to the morning you leave.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="ch-section ch-section-alt">
    <div class="container">
        <div class="ch-section-intro">
            <p class="ch-kicker">Why direct</p>
            <h2>Built for guests who value clarity</h2>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="ch-feature">
                    <span>01</span>
                    <strong>Best rate guarantee</strong>
                    <p>No OTA markup. The rate you see is the rate you pay, handled directly by the house.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="ch-feature">
                    <span>02</span>
                    <strong>Personal response</strong>
                    <p>A real person at the other end. Your questions are answered by someone who knows the house.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="ch-feature">
                    <span>03</span>
                    <strong>Honest presentation</strong>
                    <p>What you see is what you get. No stock photos, no misleading descriptions.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="ch-editorial-cta">
    <div class="container">
        <div class="ch-editorial-panel">
            <p class="ch-kicker">Stay with us</p>
            <h2>See the house for yourself.</h2>
            <a class="btn btn-ch-book" href="{{ route('booking.search') }}">Check availability</a>
        </div>
    </div>
</section>
@endsection
