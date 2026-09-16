@extends('layouts.website.app')

@section('title', 'Booking Request Received')
@section('robots', 'noindex, nofollow')

@section('content')

<header class="conf-hero">
    <div class="conf-hero-inner">
        <p class="conf-hero-kicker">Direct booking &middot; Corner House, Braunston</p>
        <h1>Your booking request has been received</h1>
        <p class="conf-hero-sub">
            Thank you. Your dates are held for 48 hours while we review your request &mdash; no payment is taken now.
        </p>
    </div>
</header>

<section class="conf-sheet">
    <div class="conf-inner">
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
                    We will email you soon to confirm the dates@if ($enquiry && $enquiry->room), then ask for a photo ID and a signed rental agreement from the lead guest@endif. Once that is done we will send you a secure payment link to settle the refundable &pound;950 deposit.
                </p>
            </div>
        </div>

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
                <strong>Hold:</strong> your dates are held for 48 hours while we review your request. If the hold expires before we confirm, please send a new request or email us and we will do our best to reinstate it.
            </p>
        @else
            <p class="conf-hint" style="margin-top:1.25rem;">
                Could not find that booking request. If you have questions, email <a href="mailto:{{ $site['config']['enquiryEmail'] ?? 'hello@cornerhousebraunston.uk' }}">{{ $site['config']['enquiryEmail'] ?? 'hello@cornerhousebraunston.uk' }}</a> and we will help.
            </p>
        @endif

        <div class="conf-actions">
            <a class="btn btn-primary" href="{{ route('home') }}">Back to the house</a>
        </div>
    </div>
</section>

@endsection