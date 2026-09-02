<section class="page" id="page-rooms" data-page="rooms">
    <div class="section">
        <div class="wrap">
            <h1 style="font-size:clamp(2.4rem,5vw,4rem)">The house, room by room</h1>
            <p class="lede">Four thousand square feet over three floors and a cellar, plus the garden buildings. The house is let as a whole; individual room bookings may be offered later.</p>
            <nav class="jump" aria-label="On this page">
                <a href="#bedrooms">Five bedrooms</a>
                <a href="#inside">Inside</a>
                <a href="#outside">Outside and the grounds</a>
            </nav>

            <h2 id="bedrooms" class="band">Five bedrooms</h2>
            <p>Every bedroom is named for one of the Big Five, a nod to the Serengeti Spirits made here, and every one has its own ensuite. Beds are Hypnos throughout, and each room has a large TV with its own Sky puck. Two rooms convert from a king to twin singles.</p>

            <ol class="rooms" id="rooms-list">
                @forelse ($site['rooms'] as $index => $room)
                    @php
                        $image = $room->primaryImage();
                        $features = is_array($room->features) ? $room->features : [];
                    @endphp
                    <li class="room">
                        <div class="num">{{ $index + 1 }}</div>
                        <div><h3>{{ $room->name }}</h3><p class="who">{{ $room->type }}</p></div>
                        <div>
                            <p>{!! nl2br(e($room->description)) !!}</p>
                            @if ($features)
                                <ul class="feat">
                                    @foreach ($features as $feature)
                                        <li>{{ $feature }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                        <div class="photo">
                            @if($image)
                                <img src="{{ asset('storage/'.$image->path) }}" alt="{{ $room->name }}">
                            @else
                                {{ $room->name }} photo
                            @endif
                        </div>
                    </li>
                @empty
                    <li class="room">
                        <div class="num">1</div>
                        <div><h3>Lion</h3><p class="who">Master suite</p></div>
                        <div><p>Publish your rooms in the admin to show them here.</p></div>
                        <div class="photo">Room photo</div>
                    </li>
                @endforelse
            </ol>

            <h2 id="inside" class="band">Inside</h2>
            <p>The living space beyond the bedrooms, arranged around the kitchen on the ground floor with the cinema room below.</p>
            <ul class="spaces">
                @foreach ($site['spacesInside'] as $space)
                    <li class="{{ isset($space['feature']) ? 'space feature' : 'space' }}">
                        <div class="photo">{{ $space['label'] }}</div>
                        <h3>{{ $space['name'] }}</h3>
                        <p class="where">{{ $space['where'] }}</p>
                        <p>{{ $space['description'] }}</p>
                    </li>
                @endforeach
            </ul>

            <h2 id="outside" class="band">Outside and the grounds</h2>
            <p>The garden is laid out for a full house: somewhere to cook, somewhere to drink, somewhere to train and somewhere to work.</p>
            <ul class="spaces">
                @foreach ($site['spacesOutside'] as $space)
                    <li class="{{ isset($space['feature']) ? 'space feature' : 'space' }}">
                        <div class="photo">{{ $space['label'] }}</div>
                        <h3>{{ $space['name'] }}</h3>
                        <p class="where">{{ $space['where'] }}</p>
                        <p>{{ $space['description'] }}</p>
                    </li>
                @endforeach
            </ul>

            <div style="margin-top:3rem"><a class="btn btn-primary" href="#book">Check availability</a></div>
        </div>
    </div>
</section>
