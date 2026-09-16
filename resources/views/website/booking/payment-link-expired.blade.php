@extends('layouts.website.app')

@section('title', 'Payment Link Expired')
@section('robots', 'noindex, nofollow')

@section('content')

<header class="conf-hero">
    <div class="conf-hero-inner">
        <p class="conf-hero-kicker">Corner House, Braunston</p>
        <h1>This payment link has expired</h1>
        <p class="conf-hero-sub">
            Payment links are valid for 24 hours. Please contact us and we will send you a fresh one.
        </p>
    </div>
</header>

<section class="conf-sheet">
    <div class="conf-inner">
        <div class="conf-intro">
            <div class="conf-intro-mark" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 2h6" /><path d="M12 2v4" /><path d="M5 8h14" /><path d="M6 8v10a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V8" />
                </svg>
            </div>
            <div class="conf-intro-copy">
                <p class="conf-kicker">Need help?</p>
                <h2>Your dates are still safe</h2>
                <p class="conf-lede">
                    If you have not paid yet, email
                    <a href="mailto:{{ $site['config']['enquiryEmail'] ?? 'hello@cornerhousebraunston.uk' }}">{{ $site['config']['enquiryEmail'] ?? 'hello@cornerhousebraunston.uk' }}</a>
                    or reply to the payment email you received and we will send a new link within 24 hours. Your booking is not lost and the dates stay held while we sort it out.
                </p>
            </div>
        </div>

        <div class="conf-actions">
            <a class="btn btn-primary" href="{{ route('home') }}">Back to the house</a>
        </div>
    </div>
</section>

@endsection