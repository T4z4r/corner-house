<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $propertyName) | Corner House, Braunston</title>
    <meta name="description" content="Corner House is a 175-year-old ivy-clad country house in Braunston, the Heart of the Waterways. Five ensuite bedrooms, a 25-foot kitchen, hot tub, cinema room and gym. Sleeps 12 adults and 2 children.">
    @if ($site['favicon'])
        <link rel="icon" href="{{ asset('storage/'.$site['favicon']) }}">
    @endif
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $propertyName }}">
    <meta property="og:title" content="{{ $propertyName }}, Braunston">
    @if ($site['og_image'])
        <meta property="og:image" content="{{ asset('storage/'.$site['og_image']) }}">
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,300;0,9..144,400;0,9..144,600;1,9..144,300;1,9..144,400&family=Karla:ital,wght@0,400;0,500;0,700;1,400&display=swap" rel="stylesheet">
    @vite(['resources/css/website.css', 'resources/js/website.js'])
    @stack('styles')
</head>
<body>

<header class="site-header">
    <div class="wrap">
        <a class="brand" href="#home" aria-label="Corner House, Braunston - home">
            <img src="{{ $site['logo'] ? asset('storage/'.$site['logo']) : asset('images/logo.png') }}" alt="{{ $propertyName }}">
        </a>
        <button class="nav-toggle" aria-expanded="false" aria-controls="nav">Menu</button>
        <nav class="nav" id="nav" aria-label="Main">
            <a href="#home" data-nav="home">Home</a>
            <a href="#about" data-nav="about">About</a>
            <a href="#rooms" data-nav="rooms">The house</a>
            <a href="#places" data-nav="places">Places of interest</a>
            <a href="#spirits" data-nav="spirits">Serengeti Spirits</a>
            <a href="#foundation" data-nav="foundation">Wright Foundation &amp; Sustainability</a>
            <a href="#book" data-nav="book" class="btn btn-primary">Check availability</a>
        </nav>
    </div>
</header>

<main>
    @yield('content')
</main>

<footer class="site-footer">
    <div class="wrap">
        <div>
            @if ($site['footer_logo'])
                <img class="footer-logo" src="{{ asset('storage/'.$site['footer_logo']) }}" alt="{{ $propertyName }}">
            @else
                <img class="footer-logo" src="{{ asset('images/logo.png') }}" alt="{{ $propertyName }}">
            @endif
            <h4>{{ $propertyName }}</h4>
            <p>{{ $site['footer_address'] }}<br>{{ $site['footer_capacity_note'] }}</p>
            <p><a href="mailto:{{ $site['contact_email'] }}" id="footer-email">{{ $site['contact_email'] }}</a></p>
        </div>
        <div>
            <h4>The house</h4>
            <ul>
                <li><a href="#about">About</a></li>
                <li><a href="#rooms">The house</a></li>
                <li><a href="#places">Places of interest</a></li>
                <li><a href="#book">Make a booking</a></li>
            </ul>
        </div>
        <div>
            <h4>Serengeti Spirits</h4>
            <ul>
                <li><a href="#spirits">Spirits and drinks packages</a></li>
                <li><a href="#foundation">Wright Foundation</a></li>
                <li><a href="{{ $site['spirits_website'] ?? 'https://www.serengetispirits.com' }}" target="_blank" rel="noopener">serengetispirits.com</a></li>
            </ul>
        </div>
        <div>
            <h4>Booking</h4>
            <ul>
                <li><a href="#book">Make a booking</a></li>
                <li><a href="#rules">Booking rules</a></li>
                <li><a href="#house-rules">House rules</a></li>
                <li><a href="#terms">Terms and conditions</a></li>
                <li><a href="#refunds">Refund policy</a></li>
            </ul>
        </div>
        <p class="copyright">&copy; <span id="year"></span> {{ $propertyName }}, Braunston. Serengeti Spirits is a separate business; purchases are completed on its own website.</p>
    </div>
</footer>

<script>
    window.__SITE__ = @json($site['config']);
</script>
<x-website-chat-widget />
@stack('scripts')
</body>
</html>
