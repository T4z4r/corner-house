@extends('layouts.website.app')

@section('title', $reservation && $reservation->status === 'confirmed' ? 'Booking Confirmed' : 'Reservation Received')
@section('robots', 'noindex, nofollow')

@section('content')

@php
    $guest = $reservation?->guest;
    $guestName = $guest?->full_name ?: null;
    $guestEmail = $guest?->email ?: null;
    $nights = $reservation && $reservation->check_in && $reservation->check_out
        ? $reservation->check_in->diffInDays($reservation->check_out)
        : null;
    $stayLabel = $reservation?->room?->name ?: ($reservation?->property?->name ?: 'Corner House, Braunston');
    $accommodation = $reservation?->room ? 'Private stay' : 'The whole house';
    $status = $reservation->status ?? null;
    $paymentStatus = $reservation->payment_status ?? null;
    $isConfirmed = $status === 'confirmed';
    $isPaid = $paymentStatus === 'paid';
    $isPartial = $paymentStatus === 'partial';
    $money = fn ($amount) => '&pound;'.number_format((float) ($amount ?? 0), 2);
@endphp

<header class="conf-hero">
    <div class="conf-hero-inner">
        <p class="conf-hero-kicker">Direct booking &middot; Corner House, Braunston</p>
        <h1>
            {{ $isConfirmed ? 'Booking confirmed' : 'Reservation received' }}
        </h1>
        <p class="conf-hero-sub">
            {{ $isConfirmed
                ? 'Thank you, ' . ($guestName ?: 'guest') . '. We look forward to hosting you.'
                : 'We have your request and will confirm availability shortly.' }}
        </p>
    </div>
</header>

<section class="conf-sheet">
    <div class="conf-inner">
        @if ($reservation)
            {{-- Reference card --}}
            <div class="conf-intro">
                <div class="conf-intro-mark" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 6 9 17l-5-5" />
                    </svg>
                </div>
                <div class="conf-intro-copy">
                    <p class="conf-kicker">{{ $isConfirmed ? 'Booking locked in' : 'Reference issued' }}</p>
                    <h2>
                        @if ($isConfirmed)
                            {{ $stayLabel }} awaits{{ $nights ? ' &mdash; '.$nights.' night'.($nights > 1 ? 's' : '') : '' }}
                        @else
                            Your stay at {{ $stayLabel }}{{ $nights ? ' for '.$nights.' night'.($nights > 1 ? 's' : '') : '' }}
                        @endif
                    </h2>
                    <p class="conf-lede">
                        @if ($isConfirmed)
                            A confirmation email is on its way to you. Keep your reference safe &mdash; you&rsquo;ll need it for any changes or questions.
                        @else
                            Once confirmed, your booking summary arrives by email. This reference identifies your request.
                        @endif
                    </p>
                </div>
                <div class="conf-reference">
                    <span class="conf-reference-label">Reservation reference</span>
                    <strong>{{ $reservation->reference }}</strong>
                </div>
            </div>

            <div class="conf-grid">
                {{-- Main column --}}
                <div class="conf-main">
                    {{-- Your stay --}}
                    <section class="conf-card">
                        <header class="conf-card-head">
                            <h3>Your stay</h3>
                            <span class="conf-badge {{ $isConfirmed ? 'ok' : 'wait' }}">
                                @if ($isConfirmed)
                                    <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5" /></svg>
                                    Confirmed
                                @else
                                    Awaiting confirmation
                                @endif
                            </span>
                        </header>

                        <div class="conf-room">
                            <div class="conf-room-mark">
                                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6" />
                                </svg>
                            </div>
                            <div>
                                <h4>{{ $stayLabel }}</h4>
                                <p>{{ $accommodation }}{{ $reservation->property?->city ? ' &middot; '.$reservation->property->city.', United Kingdom' : '' }}</p>
                            </div>
                        </div>

                        <div class="conf-dates">
                            <div class="conf-date">
                                <small>Check-in</small>
                                <strong>{{ $reservation->check_in->format('D j M Y') }}</strong>
                            </div>
                            <div class="conf-date">
                                <small>Check-out</small>
                                <strong>{{ $reservation->check_out->format('D j M Y') }}</strong>
                            </div>
                        </div>

                        <div class="conf-meta">
                            <div>
                                <small>Duration</small>
                                <strong>{{ $nights }} night{{ $nights > 1 ? 's' : '' }}</strong>
                            </div>
                            <div>
                                <small>Guests</small>
                                <strong>{{ $reservation->guests_count ?: '-' }} {{ $reservation->guests_count == 1 ? 'guest' : 'guests' }}</strong>
                            </div>
                            <div>
                                <small>Booking source</small>
                                <strong>{{ ucfirst($reservation->source ?? 'direct') }}</strong>
                            </div>
                        </div>
                    </section>

                    {{-- Guest details --}}
                    @if ($guestName || $guestEmail)
                    <section class="conf-card">
                        <header class="conf-card-head">
                            <h3>Guest details</h3>
                            <span class="conf-badge wait">Direct</span>
                        </header>
                        <div class="conf-facts">
                            @if ($guestName)
                                <div class="conf-fact">
                                    <span class="conf-fact-icon">
                                        <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M20 21a8 8 0 0 0-16 0M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" />
                                        </svg>
                                    </span>
                                    <div>
                                        <small>Lead guest</small>
                                        <strong>{{ $guestName }}</strong>
                                    </div>
                                </div>
                            @endif
                            @if ($guestEmail)
                                <div class="conf-fact">
                                    <span class="conf-fact-icon">
                                        <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                            <rect x="3" y="5" width="18" height="14" rx="2" />
                                            <path d="m3 7 9 6 9-6" />
                                        </svg>
                                    </span>
                                    <div>
                                        <small>Email</small>
                                        <strong><a href="mailto:{{ $guestEmail }}">{{ $guestEmail }}</a></strong>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </section>
                    @endif

                    {{-- What happens next --}}
                    <section class="conf-steps">
                        <div class="conf-step">
                            <span class="conf-step-num">01</span>
                            <h4>Confirmation email</h4>
                            <p>{{ $isConfirmed ? 'Your booking summary has been emailed to you. Arrival details follow closer to your stay.' : 'Once we confirm availability, your booking summary arrives by email.' }}</p>
                        </div>
                        <div class="conf-step">
                            <span class="conf-step-num">02</span>
                            <h4>Arrival details</h4>
                            <p>You will receive the address, key access and check-in instructions before your arrival date.</p>
                        </div>
                        <div class="conf-step">
                            <span class="conf-step-num">03</span>
                            <h4>Enjoy your stay</h4>
                            <p>Make yourself at home at Corner House &mdash; the whole house, 12 guests, every comfort.</p>
                        </div>
                    </section>
                </div>

                {{-- Payment summary --}}
                <aside class="conf-aside">
                    <section class="conf-card conf-summary">
                        <header class="conf-card-head">
                            <h3>Payment summary</h3>
                            @if ($isPaid)
                                <span class="conf-badge ok">Paid</span>
                            @elseif ($isPartial)
                                <span class="conf-badge warn">Partial</span>
                            @else
                                <span class="conf-badge wait">Pending</span>
                            @endif
                        </header>

                        <div class="conf-rows">
                            <div class="conf-row">
                                <span>Accommodation <small>&middot; {{ $nights }} night{{ $nights > 1 ? 's' : '' }}</small></span>
                                <span>{!! $money($reservation->base_amount) !!}</span>
                            </div>
                            @if ((float) $reservation->discount_amount > 0)
                            <div class="conf-row discount">
                                <span>Direct booking saving</span>
                                <span>-{!! $money($reservation->discount_amount) !!}</span>
                            </div>
                            @endif
                            @if ((float) $reservation->fees_amount > 0)
                            <div class="conf-row">
                                <span>Cleaning &amp; fees</span>
                                <span>{!! $money($reservation->fees_amount) !!}</span>
                            </div>
                            @endif
                            @if ((float) $reservation->tax_amount > 0)
                            <div class="conf-row">
                                <span>Taxes</span>
                                <span>{!! $money($reservation->tax_amount) !!}</span>
                            </div>
                            @endif
                        </div>

                        <div class="conf-total">
                            <span>Total</span>
                            <strong>{!! $money($reservation->total_amount) !!}</strong>
                        </div>

                        @if ($isPaid)
                            <p class="conf-note">
                                <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex:0 0 auto"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                Payment received securely. Your reservation reference is <strong>{{ $reservation->reference }}</strong>.
                            </p>
                        @else
                            <p class="conf-note">
                                <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex:0 0 auto"><path d="M12 8v4M12 16h.01"/><path d="M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20Z"/></svg>
                                Payment of {!! $money($reservation->total_amount) !!} is still pending. You will be notified as soon as it clears.
                            </p>
                        @endif
                    </section>

                    {{-- Help --}}
                    <section class="conf-help">
                        <h4>Need a hand?</h4>
                        <p>Questions about your stay, dates or payment? Our team is happy to help you before you arrive.</p>
                        <a href="mailto:{{ $site['contact_email'] }}">{{ $site['contact_email'] }}</a>
                    </section>
                </aside>
            </div>
        @else
            {{-- No reservation found --}}
            <div class="conf-empty">
                <div class="conf-go-empty" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="9" />
                        <path d="M12 8v4M12 16h.01" />
                    </svg>
                </div>
                <p class="conf-kicker">We could not find that booking</p>
                <h2>Waiting for confirmation</h2>
                <p class="conf-muted">If you have just paid, please wait a moment and check your email inbox. Paying again could create a duplicate reservation.</p>
                <a class="btn btn-primary" href="{{ url('#book') }}">Back to booking</a>
            </div>
        @endif
    </div>
</section>

<style>
/* ===== Corner House Booking Confirmation ===== */
.conf-sheet{
  --conf-ink:#1E211C;
  --conf-muted:#4F554B;
  --conf-line:rgba(31,56,38,.14);
  padding:clamp(2rem,5vw,3.5rem) var(--gutter);
}
.conf-inner{max-width:980px;margin:0 auto}

/* Hero */
.conf-hero{
  position:relative;overflow:hidden;
  background:radial-gradient(120% 140% at 92% 0%,rgba(180,85,43,.32),transparent 46%),
             radial-gradient(90% 120% at 0% 100%,rgba(126,154,122,.22),transparent 55%),
             linear-gradient(155deg,#1F3826,#2F5136 62%,#233E2B);
  color:var(--stone);
  padding:clamp(3rem,7vw,5.2rem) var(--gutter) clamp(2.4rem,5vw,3.4rem);
  text-align:center;
}
.conf-hero::after{
  content:"";position:absolute;left:0;right:0;bottom:0;height:1px;
  background:linear-gradient(90deg,transparent,rgba(214,223,205,.4),transparent);
}
.conf-hero-kicker{
  margin:0 0 .9rem;font-size:.78rem;font-weight:700;letter-spacing:.2em;text-transform:uppercase;color:var(--sage);
}
.conf-hero h1{
  color:var(--stone);margin:0 auto .9rem;
  font-size:clamp(2.3rem,5.2vw,3.6rem);font-variation-settings:"opsz" 144;
  max-width:22ch;
}
.conf-hero-sub{margin:0 auto;max-width:52ch;color:var(--stone);opacity:.85}

/* Intro / reference card */
.conf-intro{
  display:grid;grid-template-columns:auto 1fr auto;gap:1.4rem;align-items:center;
  background:linear-gradient(180deg,#fff 0%,var(--stone-light) 100%);
  border:1px solid var(--conf-line);border-radius:16px;
  padding:clamp(1.6rem,4vw,2.4rem) clamp(1.4rem,4vw,2.4rem);
  margin:-2.4rem 0 2.4rem;position:relative;
  box-shadow:0 30px 70px -42px rgba(31,56,38,.55);
}
@media (max-width:720px){.conf-intro{grid-template-columns:1fr;text-align:center;margin-top:-2.2rem}}
.conf-intro-mark{
  width:64px;height:64px;border-radius:50%;
  background:linear-gradient(150deg,var(--ivy),var(--ivy-deep));
  color:var(--stone);display:grid;place-items:center;
  box-shadow:0 14px 30px -12px rgba(31,56,38,.55);
  border:3px solid rgba(255,255,255,.6);outline:6px solid var(--stone);
}
@media (max-width:720px){.conf-intro-mark{margin:0 auto}}
.conf-intro-copy .conf-kicker{margin-bottom:.45rem}
.conf-intro h2{margin:0 0 .4rem;font-size:clamp(1.5rem,3vw,2rem)}
.conf-lede{margin:0;color:var(--conf-muted)}
.conf-reference{
  display:flex;flex-direction:column;gap:.15rem;text-align:center;
  padding:.85rem 1.5rem;background:#fff;border:1px solid var(--conf-line);border-radius:10px;
  white-space:nowrap;
}
.conf-reference-label{font-size:.68rem;font-weight:700;letter-spacing:.16em;text-transform:uppercase;color:var(--conf-muted)}
.conf-reference strong{font-family:var(--display);font-size:1.4rem;letter-spacing:.14em;color:var(--ivy-deep)}
@media (max-width:500px){.conf-reference{white-space:normal}}
.conf-kicker{font-size:.75rem;font-weight:700;letter-spacing:.2em;text-transform:uppercase;color:var(--terracotta-deep);margin:0}

/* Grid */
.conf-grid{display:grid;grid-template-columns:1.55fr 1fr;gap:1.5rem;align-items:start}
@media (max-width:880px){.conf-grid{grid-template-columns:1fr}}
.conf-main{display:grid;gap:1.5rem}

/* Cards */
.conf-card{background:#fff;border:1px solid var(--conf-line);border-radius:12px;padding:clamp(1.2rem,3vw,1.7rem);box-shadow:0 16px 40px -32px rgba(31,56,38,.3)}
.conf-card-head{display:flex;align-items:center;justify-content:space-between;gap:1rem;border-bottom:1px solid var(--conf-line);padding-bottom:.95rem;margin-bottom:1.2rem}
.conf-card-head h3{margin:0;font-size:1.35rem}
.conf-badge{display:inline-flex;align-items:center;gap:.4rem;font-size:.72rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;padding:.36rem .75rem;border-radius:999px;white-space:nowrap}
.conf-badge.ok{background:var(--sage);color:var(--ivy-deep)}
.conf-badge.warn{background:var(--terracotta-tint);color:var(--terracotta-deep)}
.conf-badge.wait{background:var(--stone);color:var(--ink-soft)}

/* Room header */
.conf-room{display:flex;gap:1rem;align-items:center;margin-bottom:1.2rem}
.conf-room-mark{width:48px;height:48px;flex:0 0 auto;border-radius:10px;background:var(--stone);display:grid;place-items:center;color:var(--ivy-deep)}
.conf-room h4{margin:0 0 .15rem;font-size:1.2rem}
.conf-room p{margin:0;font-size:.9rem;color:var(--conf-muted)}

/* Dates */
.conf-dates{display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1.1rem}
@media (max-width:440px){.conf-dates{grid-template-columns:1fr}}
.conf-date{display:flex;flex-direction:column;gap:.2rem;background:var(--stone-light);border:1px solid var(--conf-line);border-radius:10px;padding:.95rem 1.1rem}
.conf-date small{font-size:.7rem;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:var(--conf-muted)}
.conf-date strong{font-family:var(--display);font-size:1.25rem;font-weight:400;color:var(--ivy-deep)}

/* Meta */
.conf-meta{display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;border-top:1px solid var(--conf-line);padding-top:1rem}
@media (max-width:460px){.conf-meta{grid-template-columns:1fr}}
.conf-meta div{display:flex;flex-direction:column;gap:.15rem}
.conf-meta small{font-size:.7rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--conf-muted)}
.conf-meta strong{font-weight:700;color:var(--conf-ink);word-break:break-word}

/* Guest facts */
.conf-facts{display:grid;gap:1rem}
.conf-fact{display:flex;gap:.9rem;align-items:center}
.conf-fact-icon{width:38px;height:38px;flex:0 0 auto;border-radius:50%;background:var(--stone);display:grid;place-items:center;color:var(--terracotta-deep)}
.conf-fact small{display:block;font-size:.7rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--conf-muted)}
.conf-fact strong{color:var(--conf-ink);font-weight:700;word-break:break-word}

/* Next steps */
.conf-steps{display:grid;grid-template-columns:repeat(3,1fr);gap:1.2rem}
@media (max-width:700px){.conf-steps{grid-template-columns:1fr}}
.conf-step{background:var(--stone-light);border:1px solid var(--conf-line);border-radius:10px;padding:1.35rem 1.4rem}
.conf-step-num{font-family:var(--display);font-size:1.7rem;font-weight:300;color:var(--terracotta);line-height:1}
.conf-step h4{margin:.6rem 0 .35rem;font-size:1.05rem}
.conf-step p{margin:0;font-size:.9rem;color:var(--conf-muted)}

/* Aside */
.conf-aside{display:grid;gap:1.5rem;position:sticky;top:108px}
@media (max-width:880px){.conf-aside{position:static}}
.conf-rows{display:grid}
.conf-row{display:flex;align-items:baseline;justify-content:space-between;gap:1rem;padding:.45rem 0;font-size:.97rem;color:var(--conf-ink)}
.conf-row small{color:var(--conf-muted);font-size:.85rem}
.conf-row.discount{color:var(--ivy)}
.conf-total{display:flex;align-items:baseline;justify-content:space-between;gap:1rem;border-top:2px solid var(--ivy-deep);margin-top:.65rem;padding-top:.85rem}
.conf-total span{font-size:.82rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--conf-muted)}
.conf-total strong{font-family:var(--display);font-size:1.8rem;font-weight:400;color:var(--ivy-deep)}
.conf-note{display:flex;gap:.65rem;align-items:flex-start;background:var(--stone);border-radius:8px;padding:.9rem 1rem;margin:1.2rem 0 0;font-size:.88rem;color:var(--conf-muted)}
.conf-note strong{color:var(--conf-ink)}

.conf-help{background:linear-gradient(160deg,#233E2B,#1F3826);color:var(--stone);border-radius:12px;padding:1.6rem 1.7rem}
.conf-help h4{color:var(--stone);margin:0 0 .45rem;font-size:1.2rem}
.conf-help p{margin:0 0 .8rem;font-size:.92rem;color:var(--sage);max-width:34ch}
.conf-help a{color:var(--terracotta-tint);font-weight:700}

/* Empty state */
.conf-empty{max-width:540px;margin:0 auto;text-align:center;background:#fff;border:1px solid var(--conf-line);border-radius:16px;padding:clamp(2.4rem,6vw,3.4rem) clamp(1.5rem,5vw,2.6rem);box-shadow:0 30px 70px -40px rgba(31,56,38,.4)}
.conf-go-empty{width:64px;height:64px;margin:0 auto 1.2rem;border-radius:50%;background:var(--terracotta-tint);color:var(--terracotta-deep);display:grid;place-items:center}
.conf-empty h2{margin:0 0 .6rem;font-size:1.7rem}
.conf-empty .conf-muted{color:var(--conf-muted);margin:0 0 1.4rem}
</style>

@endsection