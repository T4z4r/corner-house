<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $propertyName) | {{ $propertyName }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;0,700;1,500&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="ch-website">
<div class="ch-site-grain" aria-hidden="true"></div>
<nav class="navbar navbar-expand-lg ch-public-nav sticky-top">
    <div class="container">
        <a class="navbar-brand" href="{{ route('home') }}">
            <span class="ch-brand-mark"></span>{{ $propertyName }}
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#publicNav" aria-label="Menu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="publicNav">
            <ul class="navbar-nav ms-auto align-items-lg-center">
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('property') ? 'active' : '' }}" href="{{ route('property') }}">The House</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('amenities') ? 'active' : '' }}" href="{{ route('amenities') }}">Amenities</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('gallery') ? 'active' : '' }}" href="{{ route('gallery') }}">Gallery</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('food-drink') ? 'active' : '' }}" href="{{ route('food-drink') }}">Food & Drink</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('places') ? 'active' : '' }}" href="{{ route('places') }}">Places</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('location') ? 'active' : '' }}" href="{{ route('location') }}">Location</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('faq') ? 'active' : '' }}" href="{{ route('faq') }}">FAQ</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('contact') ? 'active' : '' }}" href="{{ route('contact') }}">Contact</a></li>
                <li class="nav-item"><a class="btn btn-ch-book ms-lg-3 {{ request()->routeIs('booking.*') ? 'active' : '' }}" href="{{ route('booking.search') }}">Book now</a></li>
            </ul>
        </div>
    </div>
</nav>

<main>
    @if (session('status'))
        <div class="container pt-4">
            <div class="alert alert-success">{{ session('status') }}</div>
        </div>
    @endif
    @if ($errors->any())
        <div class="container pt-4">
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        </div>
    @endif
    @yield('content')
</main>

<footer class="ch-public-footer">
    <div class="container">
        <div class="row g-5 py-5">
            <div class="col-lg-5">
                <div class="ch-footer-brand">{{ $propertyName }}</div>
                <p class="ch-footer-lead">An independently hosted stay. Book direct for the quietest rate and a more considered welcome.</p>
            </div>
            <div class="col-6 col-lg-3">
                <div class="ch-footer-heading">Stay</div>
                <a href="{{ route('booking.search') }}">Availability</a>
                <a href="{{ route('property') }}">The House</a>
                <a href="{{ route('amenities') }}">Amenities</a>
                <a href="{{ route('gallery') }}">Gallery</a>
            </div>
            <div class="col-6 col-lg-4">
                <div class="ch-footer-heading">Details</div>
                <a href="{{ route('cancellation') }}">Cancellation policy</a>
                <a href="{{ route('privacy') }}">Privacy</a>
                <a href="{{ route('terms') }}">Terms</a>
                <a href="{{ route('login') }}">Staff login</a>
            </div>
        </div>
        <div class="ch-footer-bar">Direct booking · Northamptonshire</div>
    </div>
</footer>

<x-chat-widget source="website" title="Ask {{ $propertyName }}" :show-message="true" />
@stack('scripts')
</body>
</html>
