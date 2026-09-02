<section class="page" id="page-book" data-page="book">
    <div class="section">
        <div class="wrap">
            <h1 style="font-size:clamp(2.4rem,5vw,4rem)">Make a booking</h1>
            <p class="lede">Corner House is let as a whole house, to one party at a time, for a minimum of two nights. Book through Airbnb, Booking.com or Vrbo, or directly with us at 10% less.</p>
            <nav class="jump" aria-label="On this page">
                <a href="#platforms">Where to book</a>
                <a href="#availability">Availability</a>
                <a href="#rules">Booking rules</a>
                <a href="#house-rules">House rules</a>
            </nav>

            <h2 id="platforms" class="band">Where to book</h2>
            <p>We list on three platforms as well as taking bookings ourselves, and we genuinely do not mind which you use. Booking through a platform means your payment is handled by them, with their guest protection and support behind it. Booking directly is 10% cheaper than the platform rate, because we are not paying their commission.</p>
            <p class="rating-line"><strong>{{ $site['heroFacts'][5]['value'] ?? '4.95' }}</strong> {{ $site['heroFacts'][5]['label'] ?? 'average from 40 reviews on Airbnb' }}</p>
            <ul class="platforms">
                <li class="platform recommended">
                    <span class="tag">Recommended</span>
                    <h3>Airbnb</h3>
                    <p>Our main listing, with reviews and instant availability. Payment and guest protection handled by Airbnb.</p>
                    <a class="btn btn-primary" href="{{ $site['platforms']['airbnb'] ?: '#' }}" @if(!$site['platforms']['airbnb']) data-placeholder="Airbnb listing URL" @endif target="_blank" rel="noopener">Book on Airbnb</a>
                </li>
                <li class="platform recommended">
                    <span class="tag">Recommended</span>
                    <h3>Booking.com</h3>
                    <p>Book with free cancellation options and pay through Booking.com.</p>
                    <a class="btn btn-primary" href="{{ $site['platforms']['booking'] ?: '#' }}" @if(!$site['platforms']['booking']) data-placeholder="Booking.com listing URL" @endif target="_blank" rel="noopener">Book on Booking.com</a>
                </li>
                <li class="platform recommended">
                    <span class="tag">Recommended</span>
                    <h3>Vrbo</h3>
                    <p>Whole-home specialist, well suited to larger family groups.</p>
                    <a class="btn btn-primary" href="{{ $site['platforms']['vrbo'] ?: '#' }}" @if(!$site['platforms']['vrbo']) data-placeholder="Vrbo listing URL" @endif target="_blank" rel="noopener">Book on Vrbo</a>
                </li>
                <li class="platform">
                    <h3>Direct with us</h3>
                    <span class="tag alt">10% cheaper</span>
                    <p>The same house at 10% below the platform rate. Direct bookings need a signed rental agreement, photo ID from the lead guest and a refundable security deposit.</p>
                    <a class="btn btn-outline" href="#availability">Check dates and enquire</a>
                </li>
            </ul>

            <h2 id="availability" class="band">Availability</h2>
            <p>Choose your check-in and check-out dates, then send an enquiry. We reply within 24 hours to confirm the dates and the price.</p>
            <p class="notice" id="demo-notice">Availability shown is sample data until the live calendar feed is connected.</p>

            <div class="booking">
                <div>
                    <div class="cal-head">
                        <button type="button" id="prev-month" aria-label="Previous month">&lsaquo;</button>
                        <span class="small">Minimum stay <strong id="min-nights-label">2</strong> nights</span>
                        <button type="button" id="next-month" aria-label="Next month">&rsaquo;</button>
                    </div>
                    <div class="months" id="months"></div>
                    <div class="legend">
                        <span class="l-free">Available</span>
                        <span class="l-blocked">Booked</span>
                        <span class="l-sel">Your dates</span>
                    </div>
                </div>

                <aside class="quote">
                    <h3>Your stay</h3>
                    <div class="dates">
                        <div><small>Check in</small><strong id="q-in">Select a date</strong></div>
                        <div><small>Check out</small><strong id="q-out">&mdash;</strong></div>
                    </div>
                    <div class="lines" id="q-lines" hidden>
                        <div><span id="q-nights"></span><span id="q-accom"></span></div>
                        <div><span>Cleaning</span><span id="q-clean"></span></div>
                        <div class="total"><span>Estimated total</span><span id="q-total"></span></div>
                        <div class="dep"><span>Refundable security deposit</span><span id="q-dep"></span></div>
                    </div>
                    <p class="hint" id="q-hint">Whole-house booking for up to 12 adults and 2 children. Estimate only; we confirm the price when we reply.</p>
                    <p class="err" id="q-err" hidden></p>

                    <form class="enquiry" id="enquiry">
                        <div class="row">
                            <label>Name<input name="name" required autocomplete="name"></label>
                            <label>Email<input name="email" type="email" required autocomplete="email"></label>
                        </div>
                        <div class="row">
                            <label>Phone<input name="phone" type="tel" autocomplete="tel"></label>
                            <label>Guests<select name="guests"><option>2</option><option>4</option><option>6</option><option>8</option><option>10</option><option selected>12</option><option>12 + 2 children</option></select></label>
                        </div>
                        <label>Occasion or message<textarea name="message" placeholder="Birthday, family get-together, walking weekend&hellip;"></textarea></label>
                        <label class="check"><input type="checkbox" name="drinks">Add a Serengeti Spirits drinks package to my stay</label>
                        <label class="check"><input type="checkbox" name="agree" required>I have read the <a href="#rules">booking rules</a>, <a href="#house-rules">house rules</a> and <a href="#terms">terms and conditions</a></label>
                        <button class="btn btn-primary" type="submit">Send booking enquiry</button>
                    </form>
                    <p class="alt-book">An enquiry is not a confirmed booking. Dates are held only once the rental agreement is signed and the first payment has cleared.</p>
                </aside>
            </div>
        </div>
    </div>

    <div class="section stone">
        <div class="wrap">
            <h2 id="rules" class="band" style="margin-top:0;border-top:none;padding-top:0">Booking rules</h2>
            <p>These apply to bookings made directly with us. If you book through Airbnb, Booking.com or Vrbo, that platform&rsquo;s own terms and payment rules apply instead.</p>

            <div class="rules-grid">
                @foreach ($site['bookingRules'] as $rule)
                    <div class="rule">
                        <h3>{{ $rule['title'] }}</h3>
                        <ul>
                            @foreach ($rule['items'] as $item)
                                <li>{!! str_contains($item, '#refunds') ? str_replace('href="#refunds"', 'href="#refunds"', $item) : $item !!}</li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="section">
        <div class="wrap">
            <h2 id="house-rules" class="band" style="margin-top:0;border-top:none;padding-top:0">House rules</h2>
            <p>These apply to every booking, however you book. They are here to keep the house, our neighbours and you in good order. Signing the rental agreement, or booking through a platform, means you accept them on behalf of your whole party.</p>

            <div class="rules-grid">
                @foreach ($site['houseRules'] as $rule)
                    <div class="rule @if($rule['flag'] ?? false) flag @endif">
                        <h3>{{ $rule['title'] }}</h3>
                        <ul>
                            @foreach ($rule['items'] as $item)
                                <li>{!! $item !!}</li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>

            <div class="cta-row">
                <a class="btn btn-primary" href="#availability">Check availability</a>
                <a class="btn btn-outline" href="#terms">Terms and conditions</a>
                <a class="btn btn-outline" href="#refunds">Refund policy</a>
            </div>
        </div>
    </div>
</section>
