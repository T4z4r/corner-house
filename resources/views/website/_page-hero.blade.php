@php
    $kicker = $kicker ?? '';
    $title = $title ?? '';
    $subtitle = $subtitle ?? '';
@endphp
<section class="ch-page-hero">
    <div class="container">
        @if ($kicker)
            <p class="ch-kicker">{{ $kicker }}</p>
        @endif
        <h1>{{ $title }}</h1>
        @if ($subtitle)
            <p class="ch-page-hero-sub">{{ $subtitle }}</p>
        @endif
    </div>
</section>
