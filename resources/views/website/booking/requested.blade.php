@extends('layouts.website.app')

@section('title', 'Booking Request Received')
@section('robots', 'noindex, nofollow')

@push('styles')
<style>
    .conf-hero { background: #f3f4eb; text-align: center; padding: clamp(3rem, 7vw, 6rem) 1.25rem 4rem; color: #243e30; }
    .conf-hero-inner { max-width: 740px; margin: auto; }
    .conf-hero-kicker, .conf-kicker { text-transform: uppercase; letter-spacing: .14em; font-size: .75rem; font-weight: 600; color: #586c50; }
    .conf-hero h1 { font-family: Georgia, serif; font-size: clamp(2.2rem, 5vw, 3.5rem); line-height: 1.15; font-weight: 400; margin: 1rem 0; }
    .conf-hero-sub { max-width: 55ch; margin: auto; line-height: 1.8; color: #546259; }
    .conf-sheet { background: #faf9f5; padding: 0 1.25rem 4rem; display: flow-root; }
    .conf-inner { position: relative; max-width: 900px; margin: -2rem auto 0; background: white; border: 1px solid #e3e7dc; border-radius: 20px; padding: clamp(1.4rem, 4vw, 3rem); box-shadow: 0 12px 35px #263e3010; }
    .conf-intro { display: flex; gap: 1.25rem; align-items: flex-start; margin-bottom: 2rem; }
    .conf-intro-mark { flex-shrink: 0; width: 56px; height: 56px; display: grid; place-items: center; background: #eaf0e4; color: #315337; border-radius: 50%; }
    .conf-intro-copy { min-width: 0; }
    .conf-intro h2 { font-family: Georgia, serif; font-size: clamp(1.5rem, 3vw, 2rem); font-weight: 400; color: #243e30; margin: .4rem 0 .75rem; }
    .conf-lede, .conf-hint { font-size: 1rem; line-height: 1.8; color: #546259; margin-bottom: 0; }
    .conf-status { border: 1px solid #e3e7dc; border-radius: 12px; padding: .4rem 1.25rem; }
    .conf-status-row { display: flex; justify-content: space-between; gap: 1.25rem; padding: 1rem 0; border-bottom: 1px solid #eef0e9; }
    .conf-status-row:last-child { border: 0; }
    .conf-status-row span { color: #657165; }
    .conf-status-row strong { color: #243e30; text-align: right; overflow-wrap: anywhere; }
    .conf-hint { background: #f3f4eb; border-radius: 12px; padding: 1rem 1.25rem; }
    .conf-actions { display: flex; justify-content: center; flex-wrap: wrap; gap: 1rem; margin-top: 2rem; }
    .conf-actions a { padding: .9rem 1.5rem; border: 1px solid #315337; border-radius: 8px; text-decoration: none; color: #315337; font-weight: 600; }
    .conf-actions .btn-primary { background: #315337; color: white; }
    .conf-actions a:focus-visible { outline: 3px solid #b18d42; outline-offset: 4px; }
    .conf-actions a:hover { box-shadow: 0 3px 12px #263e3020; }
    @media (max-width: 600px) { .conf-intro { flex-direction: column; } .conf-status-row { flex-direction: column; gap: .35rem; } .conf-status-row strong { text-align: left; } .conf-actions a { width: 100%; text-align: center; } }
</style>
@endpush

@section('content')

<header class="conf-hero">
    <div class="conf-hero-inner">
        <p class="conf-hero-kicker">Direct booking &middot; Corner House, Braunston</p>
        <h1>{{ $enquiry ? 'Thank you for your enquiry' : 'Looking for your enquiry?' }}</h1>
        <p class="conf-hero-sub">
            {{ $enquiry ? 'Your request has been received. Our team will review your preferred dates and get back to you by email. No payment has been taken.' : 'We could not find an enquiry from this link. If you have already submitted one, please contact us before sending another request.' }}
        </p>
    </div>
</header>

<section class="conf-sheet">
    <div class="conf-inner">
        @if ($enquiry)
        <div class="conf-intro">
            <div class="conf-intro-mark" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 6 9 17l-5-5" />
                </svg>
            </div>
            <div class="conf-intro-copy">
                <p class="conf-kicker">Next steps</p>
                <h2>We are reviewing your request</h2>
                <p class="conf-lede">
                    We will check availability and email you about your stay, any details we need and the next steps. If we can accommodate your request, we will explain the payment options and provide a secure payment link. Please check your junk folder too.
                </p>
            </div>
        </div>
        @endif

        @if ($enquiry)
            <div class="conf-status">
                <div class="conf-status-row">
                    <span>Request reference</span>
                    <strong>#{{ $enquiry->id }}</strong>
                </div>
                @if ($enquiry->room)
                    <div class="conf-status-row">
                        <span>Stay</span>
                        <strong>{{ $enquiry->room->name }}</strong>
                    </div>
                @endif
                @if ($enquiry->check_in && $enquiry->check_out)
                    <div class="conf-status-row">
                        <span>Dates</span>
                        <strong>{{ $enquiry->check_in->format('d M Y') }} &rarr; {{ $enquiry->check_out->format('d M Y') }}</strong>
                    </div>
                @endif
                @if ($enquiry->guests)
                    <div class="conf-status-row">
                        <span>Guests</span>
                        <strong>{{ $enquiry->guests }}</strong>
                    </div>
                @endif
            </div>

            <p class="conf-hint" style="margin-top:1.25rem;">
                <strong>Your booking is not confirmed yet.</strong> This page acknowledges your enquiry.
                @if ($enquiry->bookingHold?->status === 'active' && $enquiry->bookingHold->expires_at?->isFuture())
                    Your dates are temporarily held until {{ $enquiry->bookingHold->expires_at->copy()->timezone('Europe/London')->format('d M Y, H:i T') }} while we review your request. Please contact us if you need more time.
                @endif
            </p>
        @else
            <p class="conf-hint" style="margin-top:1.25rem;">
                Need a hand? Email <a href="mailto:{{ $site['config']['enquiryEmail'] ?? 'hello@cornerhousebraunston.uk' }}">{{ $site['config']['enquiryEmail'] ?? 'hello@cornerhousebraunston.uk' }}</a> and our team will help you find your request.
            </p>
        @endif

        <div class="conf-actions">
            <a class="btn btn-primary" href="{{ route('home') }}">Back to Corner House</a>
            <a href="mailto:{{ $site['config']['enquiryEmail'] ?? 'hello@cornerhousebraunston.uk' }}">Contact our team</a>
        </div>
    </div>
</section>

@endsection
