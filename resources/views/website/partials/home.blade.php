<section class="page" id="page-home" data-page="home">
    <div class="hero">
        <div class="wrap">
            <div class="hero-grid"><div>
                <h1>A unique ivy-covered period house, <em>by the marina, at the heart of the Midlands</em></h1>
                <p class="lede">{{ $site['property']?->short_description ?: 'Corner House is a 175-year-old period home a few footsteps from Braunston Marina — five ensuite bedrooms, a 25ft kitchen built for entertaining, a games room and plenty of outside space, for family and friends to socialise and enjoy.' }}</p>
                <div class="hero-actions">
                    <a class="btn btn-primary" href="#book">Check availability</a>
                    <a class="btn btn-outline" href="#about">See the house</a>
                </div>
            </div>
            <div class="hero-split">
                @php
                    $heroImages = collect();
                    $main = \App\Models\Setting::getValue('website_hero_gallery_main');
                    $small = \App\Models\Setting::getValue('website_hero_gallery_small');
                @endphp
                <figure>@if($main)<img src="{{ asset('storage/'.$main) }}" alt="The ivy-covered front of Corner House from Old Road">@else<img src="{{ asset('images/hero-front.jpg') }}" alt="The ivy-covered front of Corner House from Old Road">@endif<figcaption>The front</figcaption></figure>
                <figure>@if($small)<img src="{{ asset('storage/'.$small) }}" alt="The rear of Corner House with the raised patio and landscaped garden">@else<img src="{{ asset('images/hero-garden.jpg') }}" alt="The rear of Corner House with the raised patio and landscaped garden">@endif<figcaption>The garden</figcaption></figure>
            </div>
            </div>
            <ul class="facts">
                @foreach ($site['heroFacts'] as $fact)
                    <li><strong>{{ $fact['value'] }}</strong>{{ $fact['label'] }}</li>
                @endforeach
            </ul>
        </div>
    </div>

    <div class="section">
        <div class="wrap">
            <div class="feature-row">
                <div>
                    <h2>Built for a full house</h2>
                    <p>{{ $site['property']?->description ?: 'The house is arranged around a 25-foot kitchen, with the orangery converted into a garden dining room that seats ten at a handmade farmhouse table. Downstairs there is a games room and a cinema room in the converted cellar; outside, a garden bar, a Kadai fire-pit barbecue, a fully equipped gym and a hard-wired office in the grounds.' }}</p>
                    <ul class="amenities">
                        @foreach ($site['amenities'] as $amenity)
                            <li>{{ $amenity }}</li>
                        @endforeach
                    </ul>
                </div>
                <div class="photo tall">Kitchen photo</div>
            </div>
        </div>
    </div>

    <div class="section stone">
        <div class="wrap video-tour">
            <h2>Walk through the house</h2>
            <p>A full tour, front door to garden bar, so you can see the space before you book.</p>
            <div class="video-frame">
                @if ($site['video_url'])
                    @if (preg_match('/youtu/', $site['video_url']))
                        <iframe src="{{ $site['video_url'] }}" title="Corner House video tour" allowfullscreen loading="lazy"></iframe>
                    @else
                        <video controls preload="metadata"><source src="{{ $site['video_url'] }}" type="video/mp4"></video>
                    @endif
                @else
                    <div class="video-placeholder">
                        <span class="play" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg></span>
                        <p>Property video tour goes here. Add a YouTube or Vimeo embed, or a self-hosted MP4, in the website settings.</p>
                    </div>
                @endif
            </div>
            <div class="video-caption">
                <span>Filmed across all three floors, the cinema room and the grounds.</span>
                <a href="#rooms">See the house room by room</a>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="wrap">
            <div class="feature-row flip">
                @php $marina = \App\Models\Setting::getValue('website_hero_image'); @endphp
                <div class="photo">@if($marina)<img src="{{ asset('storage/'.$marina) }}" alt="Narrowboats moored on the canal at Braunston">@else<img src="{{ asset('images/marina.jpg') }}" alt="Narrowboats moored on the canal at Braunston">@endif</div>
                <div>
                    <h2>Footsteps from the marina</h2>
                    <p>Braunston is where the Grand Union and Oxford canals meet. Walk out of the gate and you are on the towpath, with the Gongoozlers Rest canal-boat café and the Admiral Nelson pub alongside the water, historic working boats in the marina and walking routes to Willoughby and Ashby St Ledgers.</p>
                    <a class="btn btn-outline" href="#places">Places of interest</a>
                </div>
            </div>
        </div>
    </div>

    <div class="section ivy">
        <div class="wrap">
            <div class="grid-2">
                <div>
                    <h2>Distilled on site</h2>
                    <p class="lede">Serengeti Spirits is made here at Corner House. Order a case for your stay, or commission a custom-labelled bottle for a birthday, wedding or hen weekend.</p>
                </div>
                <div style="align-self:end">
                    <a class="btn btn-outline" href="#spirits">Spirits and drinks packages</a>
                </div>
            </div>
        </div>
    </div>

    <section class="reviews">
        <div class="reviews-head">
            <span class="score">{{ $site['heroFacts'][5]['value'] ?? '4.95' }}</span>
            <div>
                <h2>What guests say</h2>
                <p><span class="stars">&#9733;&#9733;&#9733;&#9733;&#9733;</span> {{ $site['heroFacts'][5]['label'] ?? '40 reviews on Airbnb' }}</p>
            </div>
        </div>
        <div class="marquee">
            <div class="marquee-track">
                @foreach ($site['reviews'] as $review)
                    <div class="review"><span class="stars">@for($i=0;$i<min(5,(int)($review['stars'] ?? 5));$i++)&#9733;@endfor</span><blockquote>{{ $review['quote'] }}</blockquote><cite>{{ $review['cite'] }}</cite></div>
                @endforeach
                @foreach ($site['reviews'] as $review)
                    <div class="review"><span class="stars">@for($i=0;$i<min(5,(int)($review['stars'] ?? 5));$i++)&#9733;@endfor</span><blockquote>{{ $review['quote'] }}</blockquote><cite>{{ $review['cite'] }}</cite></div>
                @endforeach
            </div>
        </div>
    </section>
</section>
