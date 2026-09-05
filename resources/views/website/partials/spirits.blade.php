<section class="page" id="page-spirits" data-page="spirits">
    <div class="section ivy">
        <div class="wrap">
            <div class="serengeti-mark">
                @php $spiritsLogo = \App\Models\Setting::getValue('website_spirits_logo'); @endphp
                @if($spiritsLogo)<img src="{{ asset('storage/'.$spiritsLogo) }}" alt="Serengeti Spirits">@else<img src="{{ asset('images/serengeti-logo.png') }}" alt="Serengeti Spirits">@endif
            </div>
            <h1 style="font-size:clamp(2.4rem,5vw,4rem);color:var(--stone)">Made here at Corner House</h1>
            <div class="grid-2">
                <div>
                    <p class="lede">Our African-inspired gins and spirits are made here at Corner House. Order before you arrive and they can be waiting in the kitchen when you get here.</p>
                    <p>Serengeti Spirits runs entirely on solar power and gives five per cent of its profits to the Wright Foundation, which supports children's homes and wildlife conservation in Tanzania. Every bottle bought during your stay contributes.</p>
                </div>
                <div class="photo">Serengeti Spirits bottle photo</div>
            </div>

            <div class="spirit-cards">
                <div class="spirit-card">
                    <h3>Stock the house</h3>
                    <p>Order bottles or a case of six before you arrive and we will have it chilled and ready. Choose your bottles on the Serengeti shop and mention your booking dates at checkout.</p>
                    <a class="btn btn-outline" href="https://shop.serengetispirits.com/retail" target="_blank" rel="noopener">Shop Serengeti Spirits</a>
                </div>
                <div class="spirit-card">
                    <h3>Custom bottles for the occasion</h3>
                    <p>A birthday, an anniversary, a wedding party or a hen weekend: we will make a bottle with your own label and message. Allow two weeks before your stay.</p>
                    <a class="btn btn-outline" href="https://www.serengetispirits.com/products/custom-bottle-whitelabel" target="_blank" rel="noopener">Order a custom bottle</a>
                </div>
                <div class="spirit-card">
                    <h3>Drinks package</h3>
                    <p>Add a welcome package to your booking: a selection of Serengeti gins with tonics, garnishes and glassware laid out in the garden bar. Tick the box on the enquiry form or ask us when we confirm.</p>
                    <a class="btn btn-outline" href="#book">Add to a booking</a>
                </div>
            </div>
            <p class="small" style="margin-top:2rem;color:var(--sage)">Purchases are completed on the Serengeti Spirits website. Spirits are sold to over-18s only.</p>
        </div>
    </div>
</section>