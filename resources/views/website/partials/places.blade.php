<section class="page" id="page-places" data-page="places">
    <div class="section">
        <div class="wrap">
            <h1 style="font-size:clamp(2.4rem,5vw,4rem)">Places of interest</h1>
            <p class="lede">Where to eat and drink in the village, days out across the Midlands and the Cotswolds, and the walks we send guests on.</p>

            <div class="tabs" role="tablist">
                <button class="tab" role="tab" aria-selected="true" data-tab="food">Food &amp; drink</button>
                <button class="tab" role="tab" aria-selected="false" data-tab="days">Days out</button>
                <button class="tab" role="tab" aria-selected="false" data-tab="walks">Walking routes</button>
            </div>

            <div class="panel active" id="panel-food" role="tabpanel">
                <p>Everything below is within a fifteen-minute walk of the front door. The notes are ours; we have no affiliation with any of these venues.</p>
                @if ($site['placesFood']->isNotEmpty())
                    <ul class="places">
                        @foreach ($site['placesFood'] as $place)
                            <li class="place local">
                                <h3>{{ $place->name }}</h3>
                                @if($place->distance)<p class="dist">{{ $place->distance }}</p>@endif
                                @if($place->category)<p class="cat">{{ $place->category }}</p>@endif
                                @if($place->description)<p class="note">{{ $place->description }}</p>@endif
                                @if($place->address || $place->phone || $place->website)
                                <dl class="meta">
                                    @if($place->address)<dt>Address</dt><dd>{{ $place->address }}</dd>@endif
                                    @if($place->phone)<dt>Phone</dt><dd><a href="tel:{{ preg_replace('/[^0-9+]/', '', $place->phone) }}">{{ $place->phone }}</a></dd>@endif
                                </dl>
                                @endif
                                @if($place->website)<a class="more" href="{{ $place->website }}" target="_blank" rel="noopener">Visit website</a>@endif
                            </li>
                        @endforeach
                    </ul>
                @else
                    @include('website.partials.places-food-static')
                @endif
                <p class="small" style="margin-top:1.5rem">Hours change seasonally, particularly on the canal. Please ring ahead at weekends.</p>
            </div>

            <div class="panel" id="panel-days" role="tabpanel">
                <p>Days out within about fifty miles, sorted by driving distance from the house.</p>
                @if ($site['placesDays']->isNotEmpty())
                    <ul class="places">
                        @foreach ($site['placesDays'] as $place)
                            <li class="place">
                                <h3>{{ $place->name }}</h3>
                                @if($place->distance)<p class="dist">{{ $place->distance }}</p>@endif
                                @if($place->category)<p class="cat">{{ $place->category }}</p>@endif
                                @if($place->address)<dl class="meta"><dt>Where</dt><dd>{{ $place->address }}</dd></dl>@endif
                            </li>
                        @endforeach
                    </ul>
                @else
                    @include('website.partials.places-days-static')
                @endif
                <p class="small" style="margin-top:1.5rem">Distances and drive times are approximate. Check opening times and book ahead where needed.</p>
            </div>

            <div class="panel" id="panel-walks" role="tabpanel">
                @if ($site['walks'])
                    @foreach ($site['walks'] as $walk)
                        <div class="walk">
                            <div><h3>{{ $walk['name'] }}</h3><p>{{ $walk['description'] }}</p></div>
                            <dl>
                                @if(!empty($walk['distance']))<dt>Distance</dt><dd>{{ $walk['distance'] }}</dd>@endif
                                @if(!empty($walk['time']))<dt>Time</dt><dd>{{ $walk['time'] }}</dd>@endif
                                @if(!empty($walk['terrain']))<dt>Terrain</dt><dd>{{ $walk['terrain'] }}</dd>@endif
                                @if(!empty($walk['stop']))<dt>Stop</dt><dd>{{ $walk['stop'] }}</dd>@endif
                            </dl>
                        </div>
                    @endforeach
                @endif
                <p class="small" style="margin-top:1.5rem">These three routes still need checking on the ground — distances, times and terrain are indicative until you confirm them. Route maps or GPX files can be added here.</p>
            </div>
        </div>
    </div>
</section>
