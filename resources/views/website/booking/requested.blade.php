@extends('layouts.website.app')

@section('title', 'Booking Request Received')
@section('robots', 'noindex, nofollow')

@push('styles')
<style>
    .conf-hero { position: relative; isolation: isolate; overflow: hidden; background: radial-gradient(ellipse at 15% 0%, #42604c 0, transparent 55%), linear-gradient(135deg, #213d30, #11271e); text-align: center; padding: clamp(4rem, 8vw, 7rem) 1.25rem 6rem; color: #faf7ee; }
    .conf-hero::before, .conf-hero::after { content: ''; position: absolute; z-index: -1; width: 480px; height: 480px; border: 1px solid #c6ac7130; border-radius: 50%; pointer-events: none; }
    .conf-hero::before { top: -310px; right: -80px; }
    .conf-hero::after { bottom: -400px; left: -180px; width: 700px; height: 700px; }
    .conf-hero-inner { max-width: 740px; margin: auto; }
    .conf-hero-kicker, .conf-kicker { text-transform: uppercase; letter-spacing: .14em; font-size: .75rem; font-weight: 600; color: #586c50; }
    .conf-hero .conf-hero-kicker { color: #dac698; font-size: .7rem; letter-spacing: .22em; }
    .conf-hero h1 { font-family: Georgia, serif; font-size: clamp(2.6rem, 5.8vw, 4.5rem); line-height: 1.08; letter-spacing: -.035em; font-weight: 400; margin: 1.4rem auto; max-width: 13ch; color: #fffaf0; }
    .conf-hero-sub { max-width: 52ch; margin: auto; line-height: 1.85; color: #d6dfd6; font-size: 1rem; }
    .conf-received { display: inline-flex; align-items: center; gap: .6rem; margin-top: 1.75rem; padding: .5rem 1rem; border: 1px solid #c6ac7150; border-radius: 99px; color: #eddfbc; font-size: .75rem; letter-spacing: .04em; }
    .conf-received::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: #d5bc80; }
    .conf-sheet { background: #f7f5ef; padding: 0 1.25rem clamp(3rem, 7vw, 6rem); display: flow-root; }
    .conf-inner { position: relative; max-width: 1040px; margin: -3rem auto 0; background: #fffefa; border: 1px solid #e7e4d8; border-top: 3px solid #c1a366; border-radius: 8px; padding: clamp(1.5rem, 4vw, 3.5rem); box-shadow: 0 25px 65px -25px #182e3033; }
    .conf-inner.has-enquiry { display: grid; grid-template-columns: 1.15fr 1fr; gap: 2rem 3rem; align-items: start; }
    .conf-inner.has-enquiry .conf-hint, .conf-inner.has-enquiry .conf-actions { grid-column: 1 / -1; margin-top: 0 !important; }
    .conf-intro { display: flex; flex-direction: column; gap: 1.5rem; align-items: flex-start; margin-bottom: 0; }
    .conf-intro-mark { flex-shrink: 0; width: 56px; height: 56px; display: grid; place-items: center; background: #eaf0e4; color: #315337; border-radius: 50%; }
    .conf-intro-copy { min-width: 0; }
    .conf-intro h2 { font-family: Georgia, serif; font-size: clamp(1.5rem, 3vw, 2rem); font-weight: 400; color: #243e30; margin: .4rem 0 .75rem; }
    .conf-lede, .conf-hint { font-size: 1rem; line-height: 1.8; color: #546259; margin-bottom: 0; }
    .conf-status { background: #f5f4ee; border: 1px solid #e7e4d8; border-radius: 6px; padding: 1.4rem 1.5rem .4rem; }
    .conf-status-title { text-transform: uppercase; letter-spacing: .15em; font-size: .7rem; font-weight: 600; color: #67715e; margin: 0 0 .5rem; }
    .conf-status-row:first-of-type strong { font-family: Georgia, serif; font-size: 1.5rem; font-weight: 400; }
    .conf-status-row { display: flex; justify-content: space-between; gap: 1.25rem; padding: 1rem 0; border-bottom: 1px solid #eef0e9; }
    .conf-status-row:last-child { border: 0; }
    .conf-status-row span { color: #657165; }
    .conf-status-row strong { color: #243e30; text-align: right; overflow-wrap: anywhere; }
    .conf-hint { background: transparent; border-top: 1px solid #e7e4d8; border-radius: 0; padding: 1.5rem 0 0; font-size: .875rem; }
    .conf-hint strong { color: #2d4436; }
    .conf-actions { display: flex; justify-content: center; flex-wrap: wrap; gap: 1rem; margin-top: 2rem; }
    .conf-actions a { padding: 1rem 1.75rem; border: 1px solid #bcc7b7; border-radius: 4px; text-decoration: none; color: #315337; font-size: .875rem; font-weight: 600; transition: background .2s, box-shadow .2s; }
    .conf-actions .btn-primary { background: #315337; color: white; }
    .conf-actions a:focus-visible { outline: 3px solid #b18d42; outline-offset: 4px; }
    .conf-actions a:hover { box-shadow: 0 3px 12px #263e3020; }
    @media (max-width: 760px) { .conf-inner.has-enquiry { grid-template-columns: 1fr; gap: 1.75rem; } .conf-status-row { flex-direction: column; gap: .35rem; } .conf-status-row strong { text-align: left; } .conf-actions a { width: 100%; text-align: center; } }
    @media (prefers-reduced-motion: reduce) { .conf-actions a { transition: none; } }
</style>
@endpush

@section('content')

<header class="conf-hero">
    <div class="conf-hero-inner">
        <p class="conf-hero-kicker">Direct booking &middot; Corner House, Braunston</p>
        <h1>{{ $enquiry ? 'Thank you — we have your request.' : 'Looking for your enquiry?' }}</h1>
        <p class="conf-hero-sub">
            @if ($enquiry)
                Your dates are held while we review it. No payment is taken at this stage.
            @else
                We could not find an enquiry from this link. If you have already submitted one, please contact us before sending another request.
            @endif
        </p>
        @if ($enquiry)
            <span class="conf-received">Enquiry received &middot; Awaiting confirmation</span>
        @endif
    </div>
</header>

<section class="conf-sheet">
    <div class="conf-inner {{ $enquiry ? 'has-enquiry' : '' }}">
        @if ($enquiry)
        <div class="conf-intro">
            <div class="conf-intro-mark" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 6 9 17l-5-5" />
                </svg>
            </div>
            <div class="conf-intro-copy">
                <h2>Next steps</h2>
                <ol class="conf-lede" style="padding-left:1.25rem;">
                    <li style="margin-bottom:1rem;">We review your request and check the dates.</li>
                    <li style="margin-bottom:1rem;">We email you to confirm availability and the price. For direct bookings we will also ask the lead guest for photo ID and a signed rental agreement.</li>
                    <li>Once that is returned, we send a secure link to make the first payment.</li>
                </ol>
            </div>
        </div>
        @endif

        @if ($enquiry)
            <div class="conf-status">
                <h3 class="conf-status-title">Your request</h3>
                <div class="conf-status-row">
                    <span>Reference</span>
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
                        <strong>{{ $enquiry->check_in->format('d M Y') }} to {{ $enquiry->check_out->format('d M Y') }}</strong>
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
                If anything changes in the meantime, just reply to our email.<br>
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
