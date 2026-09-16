<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @php
        $seoProperty = $site['property'] ?? null;
        $seoDescription = trim($__env->yieldContent('description', $seoProperty?->short_description ?? 'Corner House is a 175-year-old ivy-clad country house in Braunston, the Heart of the Waterways. Five ensuite bedrooms, a 25-foot kitchen, hot tub, cinema room and gym. Sleeps 12 adults and 2 children.'));
        $seoImage = ($site['og_image'] ?? null) ? asset('storage/'.$site['og_image']) : asset('images/logo.png');
        $seoCanonical = url()->current();
        $rawTitle = trim($__env->yieldContent('title', $propertyName));
        // Avoid "Corner House ... | Corner House, Braunston" when a page title already carries the brand name.
        $seoTitle = str_contains(strtolower($rawTitle), strtolower($propertyName))
            ? $rawTitle
            : $rawTitle.' | '.$propertyName.', Braunston';
        $sameAs = array_values(array_filter(array_merge(
            $site['social_links'] ?? [],
            array_values($site['platforms'] ?? []),
        )));
    @endphp
    <meta charset="utf-8">
    <style>
        @font-face {
            font-family: 'Country Flags';
            src: url('{{ asset('fonts/TwemojiCountryFlags.woff2') }}') format('woff2');
            font-display: swap;
            unicode-range: U+1F1E6-1F1FF;
        }
        select[name="phone_code"], select[name="phone_code"] option {
            font-family: 'Country Flags', Arial, sans-serif;
        }
    </style>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $seoTitle }}</title>
    <meta name="description" content="{{ $seoDescription }}">
    <meta name="robots" content="@yield('robots', 'index, follow')">
    <link rel="canonical" href="{{ $seoCanonical }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo.svg') }}">
    @if (\App\Models\Setting::getValue('website_google_site_verification'))
        <meta name="google-site-verification" content="{{ \App\Models\Setting::getValue('website_google_site_verification') }}">
    @endif
    @if (\App\Models\Setting::getValue('website_bing_site_verification'))
        <meta name="msvalidate.01" content="{{ \App\Models\Setting::getValue('website_bing_site_verification') }}">
    @endif

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $propertyName }}">
    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:description" content="{{ $seoDescription }}">
    <meta property="og:url" content="{{ $seoCanonical }}">
    <meta property="og:image" content="{{ $seoImage }}">
    <meta property="og:locale" content="en_GB">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seoTitle }}">
    <meta name="twitter:description" content="{{ $seoDescription }}">
    <meta name="twitter:image" content="{{ $seoImage }}">

    @if ($seoProperty)
        <script type="application/ld+json">
            {!! json_encode([
                '@context' => 'https://schema.org',
                '@graph' => [
                    [
                        '@type' => 'LodgingBusiness',
                        '@id' => url('/').'#business',
                        'name' => $seoProperty->name ?? $propertyName,
                        'description' => $seoDescription,
                        'image' => $seoImage,
                        'logo' => $seoImage,
                        'url' => url('/'),
                        'telephone' => $site['contact_phone'] ?? null,
                        'email' => $site['contact_email'] ?? null,
                        'sameAs' => $sameAs,
                        'address' => array_filter([
                            '@type' => 'PostalAddress',
                            'streetAddress' => trim(($seoProperty->address_line_1 ?? '').' '.($seoProperty->address_line_2 ?? '')),
                            'addressLocality' => $seoProperty->city ?? null,
                            'postalCode' => $seoProperty->postcode ?? null,
                            'addressCountry' => $seoProperty->country ?? 'GB',
                        ]),
                        'geo' => $seoProperty->latitude && $seoProperty->longitude ? [
                            '@type' => 'GeoCoordinates',
                            'latitude' => $seoProperty->latitude,
                            'longitude' => $seoProperty->longitude,
                        ] : null,
                        'numberOfRooms' => $seoProperty->bedrooms,
                        'petsAllowed' => (bool) $seoProperty->pets_allowed,
                    ],
                    [
                        '@type' => 'WebSite',
                        '@id' => url('/').'#website',
                        'name' => $propertyName,
                        'url' => url('/'),
                        'publisher' => ['@id' => url('/').'#business'],
                    ],
                ],
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
        </script>
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
        <a class="brand" href="{{ url('/') }}#home" aria-label="Corner House, Braunston - home">
            <img src="{{ $site['logo'] ? asset('storage/'.$site['logo']) : asset('images/logo.png') }}" alt="{{ $propertyName }}">
        </a>
        <button class="nav-toggle" aria-expanded="false" aria-controls="nav">Menu</button>
        <nav class="nav" id="nav" aria-label="Main">
            <a href="{{ url('/') }}#home" data-nav="home">Home</a>
            <a href="{{ url('/') }}#about" data-nav="about">About</a>
            <a href="{{ url('/') }}#rooms" data-nav="rooms">The house</a>
            <a href="{{ url('/') }}#places" data-nav="places">Places of interest</a>
            <a href="{{ url('/') }}#spirits" data-nav="spirits">Serengeti Spirits</a>
            <a href="{{ url('/') }}#foundation" data-nav="foundation">Wright Foundation &amp; Sustainability</a>
            <a href="{{ url('/') }}#book" data-nav="book" class="btn btn-primary">Check availability</a>
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
                <li><a href="{{ url('/') }}#about">About</a></li>
                <li><a href="{{ url('/') }}#rooms">The house</a></li>
                <li><a href="{{ url('/') }}#places">Places of interest</a></li>
                <li><a href="{{ url('/') }}#book">Make a booking</a></li>
            </ul>
        </div>
        <div>
            <h4>Serengeti Spirits</h4>
            <ul>
                <li><a href="{{ url('/') }}#spirits">Spirits and drinks packages</a></li>
                <li><a href="{{ url('/') }}#foundation">Wright Foundation</a></li>
                <li><a href="{{ $site['spirits_website'] ?? 'https://www.serengetispirits.com' }}" target="_blank" rel="noopener">serengetispirits.com</a></li>
            </ul>
        </div>
        <div>
            <h4>Booking</h4>
            <ul>
                <li><a href="{{ url('/') }}#book">Make a booking</a></li>
                <li><a href="{{ url('/') }}#rules">Booking rules</a></li>
                <li><a href="{{ url('/') }}#house-rules">House rules</a></li>
                <li><a href="{{ url('/') }}#terms">Terms and conditions</a></li>
                <li><a href="{{ url('/') }}#refunds">Refund policy</a></li>
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
