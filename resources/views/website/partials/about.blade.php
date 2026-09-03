<section class="page" id="page-about" data-page="about">
    <div class="section">
        <div class="wrap">
            <h1 style="font-size:clamp(2.4rem,5vw,4rem)">About Corner House</h1>
            <div class="grid-2" style="margin-top:2rem">
                <div>
                    <p class="lede">A stunning five-bedroom period country house, covered in ivy, at nearly 4,000 square feet and a few steps from Braunston Marina.</p>
                    <p>The property is built around a 25-foot centrepiece kitchen, perfect for entertaining. For family fun there is a dedicated games room and a cinema room in the converted cellar. Within the grounds you will find outside entertaining spaces, a garden bar, Kadai BBQ, a fully equipped gym and a purpose-built, hard-wired office.</p>
                    <p>The house sleeps twelve adults and two children, with every bedroom ensuite. The orangery has been converted into a garden dining room seating ten at a handmade farmhouse table and chairs.</p>
                    <p>At the end of the day, unwind in the hot tub on the patio, in the garden room, or on the balcony above, all set in a tranquil landscaped garden.</p>
                    <a class="btn btn-outline" href="#rooms">Room by room</a>
                </div>
                @php $aboutImage = \App\Models\Setting::getValue('website_about_image'); @endphp
                <div class="photo tall">@if($aboutImage)<img src="{{ asset('storage/'.$aboutImage) }}" alt="House exterior, ivy in full leaf">@else<span>House exterior, ivy in full leaf</span>@endif</div>
            </div>
        </div>
    </div>

    <div class="section stone">
        <div class="wrap">
            <div class="grid-2">
                <div class="photo">Braunston Marina and historic boats</div>
                <div>
                    <h2>About Braunston</h2>
                    <p>Braunston is a canal-side village on the Northamptonshire–Warwickshire border, right at the centre of the Midlands and often called the Heart of the Waterways. It is a village with deep roots in English history: recorded in the Domesday Book of 1086, and close to the intrigue of nearby Ashby St Ledgers, home of the Catesby family and the Gunpowder Plot of 1605.</p>
                    <p>With the arrival of the canals, Braunston flourished as a hub for boaters, traders and walkers, its wharf and junction becoming a crossroads of the inland waterways. Today Braunston Marina celebrates that heritage, preserving historic boats and canal buildings while welcoming visitors to enjoy the village's blend of history, countryside and canal life.</p>
                    <p>There is a canal-boat café, the Gongoozlers Rest, and a good pub, the Admiral Nelson, both alongside the canal.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="wrap">
            <h2>Getting here</h2>
            <div class="grid-3">
                <div><h3>By train</h3><p>Rugby station is about 8–10 miles away, with fast services to London Euston and Birmingham. Long Buckby station is also close for the West Coast Main Line.</p></div>
                <div><h3>By road</h3><p>Braunston sits just off the A45, a few minutes from the M1 (J16/J18), M6 and M45. Daventry is ten minutes away; Birmingham and Oxford are both within an hour.</p></div>
                <div><h3>By boat</h3><p>Braunston Junction is where the Grand Union and Oxford canals meet. Visitor moorings are available at the marina and along the towpath, a short walk from the house.</p></div>
            </div>
        </div>
    </div>
</section>
