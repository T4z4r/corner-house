@extends('layouts.admin.app')
@section('title', 'Airbnb via Beds24')
@section('content')
@php
    $selectedAccountName = $selectedAccount?->name ?? 'No account selected';
@endphp

<div class="ch-page-header">
    <div>
        <div class="ch-breadcrumb">Channels</div>
        <h4>Airbnb via Beds24</h4>
        <p class="ch-subtitle mb-0">
            Browse Airbnb users, listings, and reviews fetched live through Beds24.
        </p>
    </div>
    <a href="{{ route('admin.channels.integrations') }}" class="btn btn-outline-primary">Beds24 integrations</a>
</div>

<div class="alert alert-info border-0 shadow-sm mb-3">
    <div class="d-flex flex-column flex-md-row justify-content-between gap-2">
        <div>
            <strong>How this page works:</strong>
            pick a Beds24 account, open an Airbnb user, then drill into a listing to see room details and reviews.
        </div>
        <div class="small text-muted">
            If Beds24 has not linked Airbnb data yet, the page will stay empty until the channel is connected.
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.channels.airbnb') }}" class="row g-2 align-items-end">
            <div class="col-lg-5">
                <label class="form-label small text-muted">Beds24 account</label>
                <select name="account_id" class="form-select">
                    @forelse ($accounts as $account)
                        <option value="{{ $account->id }}" @selected($selectedAccount?->id === $account->id)>{{ $account->name }}</option>
                    @empty
                        <option value="">No Beds24 accounts yet</option>
                    @endforelse
                </select>
            </div>
            @if ($selectedUserId)
                <input type="hidden" name="airbnb_user_id" value="{{ $selectedUserId }}">
            @endif
            @if ($selectedListingId)
                <input type="hidden" name="airbnb_listing_id" value="{{ $selectedListingId }}">
            @endif
            @if ($selectedRoomId)
                <input type="hidden" name="room_id" value="{{ $selectedRoomId }}">
            @endif
            <div class="col-lg-2">
                <button class="btn btn-ch-primary w-100" type="submit">Load data</button>
            </div>
        </form>

        <div class="d-flex flex-wrap gap-2 align-items-center mt-3">
            <span class="badge text-bg-{{ $selectedAccount ? 'success' : 'secondary' }}">
                {{ $selectedAccount ? 'Connected' : 'No account selected' }}
            </span>
            <span class="small text-muted">{{ $selectedAccountName }}</span>
            @if ($selectedAccount)
                <span class="badge text-bg-light">{{ $selectedAccount->mappings_count }} mappings</span>
            @endif
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white">
        <div class="fw-semibold">Send to Beds24</div>
        <div class="small text-muted">Use Beds24's POST actions to import or connect Airbnb listings from here.</div>
    </div>
    <div class="card-body">
        @if (! $selectedAccount)
            <p class="text-muted mb-0">Select a Beds24 account first to unlock the Airbnb send actions.</p>
        @else
            <div class="row g-3">
                <div class="col-lg-4">
                    <form method="POST" action="{{ route('admin.channels.airbnb.actions') }}" class="border rounded p-3 h-100">
                        @csrf
                        <input type="hidden" name="account_id" value="{{ $selectedAccount->id }}">
                        <input type="hidden" name="action" value="importAsNewProperty">
                        <input type="hidden" name="airbnb_user_id" value="{{ $selectedUserId }}">
                        <input type="hidden" name="airbnb_listing_id" value="{{ $selectedListingId }}">
                        <input type="hidden" name="connect" value="full">
                        <input type="hidden" name="import_blocked_dates" value="1">
                        <input type="hidden" name="import_bookings" value="1">
                        <div class="fw-semibold">Import as new property</div>
                        <p class="small text-muted mb-3">Create a Beds24 property from the selected Airbnb listing.</p>
                        <button type="submit" class="btn btn-outline-primary w-100" @disabled(! $selectedListingId || ! $selectedUserId)>
                            Send import
                        </button>
                    </form>
                </div>
                <div class="col-lg-4">
                    <form method="POST" action="{{ route('admin.channels.airbnb.actions') }}" class="border rounded p-3 h-100">
                        @csrf
                        <input type="hidden" name="account_id" value="{{ $selectedAccount->id }}">
                        <input type="hidden" name="action" value="importToExistingProperty">
                        <input type="hidden" name="airbnb_user_id" value="{{ $selectedUserId }}">
                        <input type="hidden" name="airbnb_listing_id" value="{{ $selectedListingId }}">
                        <input type="hidden" name="connect" value="full">
                        <div class="fw-semibold">Import to existing Beds24 property</div>
                        <p class="small text-muted mb-2">Choose an existing Beds24 property mapped in the system.</p>
                        <select name="beds24_property_id" class="form-select mb-3" @disabled($beds24Properties->isEmpty())>
                            <option value="">Select Beds24 property</option>
                            @foreach ($beds24Properties as $beds24Property)
                                <option value="{{ $beds24Property['beds24_property_id'] }}">{{ $beds24Property['label'] }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-outline-primary w-100" @disabled(! $selectedListingId || ! $selectedUserId || $beds24Properties->isEmpty())>
                            Send import
                        </button>
                    </form>
                </div>
                <div class="col-lg-4">
                    <form method="POST" action="{{ route('admin.channels.airbnb.actions') }}" class="border rounded p-3 h-100">
                        @csrf
                        <input type="hidden" name="account_id" value="{{ $selectedAccount->id }}">
                        <input type="hidden" name="airbnb_user_id" value="{{ $selectedUserId }}">
                        <input type="hidden" name="airbnb_listing_id" value="{{ $selectedListingId }}">
                        <div class="fw-semibold">Connect or disconnect a room</div>
                        <p class="small text-muted mb-2">Use a mapped Beds24 room ID to connect or disconnect the listing.</p>
                        <select name="beds24_room_id" class="form-select mb-3" @disabled($beds24Rooms->isEmpty())>
                            <option value="">Select Beds24 room</option>
                            @foreach ($beds24Rooms as $beds24Room)
                                <option value="{{ $beds24Room['beds24_room_id'] }}">{{ $beds24Room['label'] }}</option>
                            @endforeach
                        </select>
                        <div class="d-grid gap-2">
                            <button type="submit" name="action" value="connectToExistingRoom" class="btn btn-outline-success" @disabled(! $selectedListingId || $beds24Rooms->isEmpty())>
                                Connect room
                            </button>
                            <button type="submit" name="action" value="disconnectRoom" class="btn btn-outline-danger" @disabled($beds24Rooms->isEmpty())>
                                Disconnect room
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white">
                <div class="fw-semibold">1. Airbnb users</div>
                <div class="small text-muted">Choose the Airbnb user Beds24 returns for this account.</div>
            </div>
            <div class="card-body">
                @if ($usersError)
                    <div class="alert alert-danger">{{ $usersError }}</div>
                @endif
                @forelse ($users as $user)
                    <div class="border-bottom py-3 {{ $selectedUserId === $user['airbnb_user_id'] ? 'bg-light rounded px-2' : '' }}">
                        <div class="d-flex justify-content-between gap-3 align-items-center">
                            <div class="d-flex gap-3 align-items-center">
                                @if ($user['picture'])
                                    <img
                                        src="{{ $user['picture'] }}"
                                        alt="{{ $user['first_name'] }}"
                                        class="rounded-circle"
                                        style="width: 42px; height: 42px; object-fit: cover;"
                                    >
                                @else
                                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                                        <span class="fw-semibold text-primary">{{ strtoupper(substr($user['first_name'], 0, 1)) }}</span>
                                    </div>
                                @endif
                                <div>
                                    <strong>{{ $user['first_name'] }}</strong>
                                    <div class="small text-muted">User ID: {{ $user['airbnb_user_id'] }}</div>
                                </div>
                            </div>
                            <a
                                href="{{ route('admin.channels.airbnb', array_filter([
                                    'account_id' => $selectedAccount?->id,
                                    'airbnb_user_id' => $user['airbnb_user_id'],
                                ], static fn ($value) => $value !== null && $value !== '')) }}"
                                class="btn btn-sm btn-outline-primary"
                            >
                                View listings
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-5">
                        <div class="display-6 text-muted mb-2"><i class="bi bi-people"></i></div>
                        <p class="mb-1">No Airbnb users loaded yet.</p>
                        <p class="small text-muted mb-0">Select an account above, then load users from Beds24.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white">
                <div class="fw-semibold">2. Airbnb listings</div>
                <div class="small text-muted">Open a listing to inspect Beds24 room details.</div>
            </div>
            <div class="card-body">
                @if ($listingsError)
                    <div class="alert alert-danger">{{ $listingsError }}</div>
                @endif
                @forelse ($listings as $listing)
                    <div class="border-bottom py-3 {{ $selectedListingId === $listing['airbnb_listing_id'] ? 'bg-light rounded px-2' : '' }}">
                        <div class="d-flex justify-content-between gap-3 align-items-start">
                            <div>
                                <strong>{{ $listing['name'] }}</strong>
                                <div class="small text-muted">Listing ID: {{ $listing['airbnb_listing_id'] }}</div>
                                <div class="small text-muted">Room ID: {{ $listing['room_id'] ?? 'Not linked' }}</div>
                                <div class="small text-muted">
                                    Status:
                                    <span class="badge text-bg-{{ $listing['enabled'] ? 'success' : 'secondary' }}">
                                        {{ $listing['enabled'] ? 'Enabled' : 'Disabled' }}
                                    </span>
                                </div>
                                @if ($listing['room_type_category'] || $listing['property_type_category'])
                                    <div class="small text-muted">
                                        {{ $listing['property_type_category'] ?? 'Unknown property type' }}
                                        @if ($listing['room_type_category'])
                                            &middot; {{ $listing['room_type_category'] }}
                                        @endif
                                    </div>
                                @endif
                            </div>
                            <div class="d-flex flex-column gap-1 flex-shrink-0">
                                <a
                                    href="{{ route('admin.channels.airbnb', array_filter([
                                        'account_id' => $selectedAccount?->id,
                                        'airbnb_user_id' => $selectedUserId,
                                        'airbnb_listing_id' => $listing['airbnb_listing_id'],
                                    ], static fn ($value) => $value !== null && $value !== '')) }}"
                                    class="btn btn-sm btn-outline-primary"
                                >
                                    Open listing
                                </a>
                                <a href="https://www.airbnb.com/rooms/{{ $listing['airbnb_listing_id'] }}"
                                   target="_blank" rel="noopener noreferrer"
                                   class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-box-arrow-up-right me-1"></i>View on Airbnb
                                </a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-5">
                        <div class="display-6 text-muted mb-2"><i class="bi bi-house-heart"></i></div>
                        <p class="mb-1">No listings yet.</p>
                        <p class="small text-muted mb-0">Pick an Airbnb user to load the listings connected through Beds24.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white">
                <div class="fw-semibold">3. Airbnb reviews</div>
                <div class="small text-muted">Reviews appear when a listing exposes a Beds24 room ID.</div>
            </div>
            <div class="card-body">
                @if ($reviewsError)
                    <div class="alert alert-danger">{{ $reviewsError }}</div>
                @endif
                @if ($selectedRoomId)
                    <div class="small text-muted mb-3">Room ID: {{ $selectedRoomId }}</div>
                @endif
                @forelse ($reviews as $review)
                    @php
                        $content = $review['content'] ?? [];
                        $reviewer = $review['reviewer'] ?? [];
                        $scoring = $review['scoring'] ?? [];
                    @endphp
                    <div class="border-bottom py-3">
                        <div class="d-flex justify-content-between gap-3">
                            <div>
                                <strong>{{ $content['headline'] ?? 'Airbnb review' }}</strong>
                                <div class="small text-muted">Review ID: {{ $review['review_id'] ?? 'N/A' }}</div>
                                <div class="small text-muted">Reviewer: {{ $reviewer['name'] ?? 'Anonymous' }}</div>
                            </div>
                            <span class="badge text-bg-secondary">{{ $scoring['review_score'] ?? 'N/A' }}</span>
                        </div>
                        @if (! empty($content['positive']))
                            <div class="small mt-2"><strong>Positive:</strong> {{ $content['positive'] }}</div>
                        @endif
                        @if (! empty($content['negative']))
                            <div class="small mt-1"><strong>Negative:</strong> {{ $content['negative'] }}</div>
                        @endif
                    </div>
                @empty
                    <div class="text-center py-5">
                        <div class="display-6 text-muted mb-2"><i class="bi bi-chat-square-text"></i></div>
                        <p class="mb-1">
                            {{ $selectedRoomId ? 'No reviews returned for this room.' : 'Select a listing to load reviews.' }}
                        </p>
                        <p class="small text-muted mb-0">Beds24 only returns reviews for connected Airbnb room IDs.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <div class="fw-semibold">Selected listing details</div>
                <div class="small text-muted">A quick summary of the listing Beds24 returned.</div>
            </div>
            <div class="card-body">
                @if ($selectedListing)
                    @php
                        $airbnbListing = $selectedListing['airbnb_listing'] ?? [];
                        $pictures = $selectedListing['pictures'] ?? [];
                    @endphp
                    @if (! empty($pictures))
                        <div class="mb-4">
                            <div class="small text-muted mb-2">Pictures</div>
                            <div class="row g-2">
                                @foreach (array_slice($pictures, 0, 6) as $picture)
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <a href="{{ $picture }}" target="_blank" rel="noopener noreferrer" class="d-block">
                                            <img
                                                src="{{ $picture }}"
                                                alt="{{ $selectedListing['name'] }} picture {{ $loop->iteration }}"
                                                class="img-fluid rounded border w-100"
                                                style="aspect-ratio: 4 / 3; object-fit: cover;"
                                            >
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="small text-muted">Listing name</div>
                            <div class="fw-semibold">{{ $selectedListing['name'] }}</div>
                        </div>
                        <div class="col-md-3">
                            <div class="small text-muted">Room ID</div>
                            <div class="fw-semibold">{{ $selectedListing['room_id'] ?? 'N/A' }}</div>
                        </div>
                        <div class="col-md-3">
                            <div class="small text-muted">Bedrooms</div>
                            <div class="fw-semibold">{{ $airbnbListing['bedrooms'] ?? 'N/A' }}</div>
                        </div>
                        <div class="col-md-3">
                            <div class="small text-muted">Bathrooms</div>
                            <div class="fw-semibold">{{ $airbnbListing['bathrooms'] ?? 'N/A' }}</div>
                        </div>
                        <div class="col-md-3">
                            <div class="small text-muted">Beds</div>
                            <div class="fw-semibold">{{ $airbnbListing['beds'] ?? 'N/A' }}</div>
                        </div>
                        <div class="col-md-3">
                            <div class="small text-muted">Property type</div>
                            <div class="fw-semibold">{{ $airbnbListing['property_type_category'] ?? 'N/A' }}</div>
                        </div>
                        <div class="col-md-3">
                            <div class="small text-muted">Room type</div>
                            <div class="fw-semibold">{{ $airbnbListing['room_type_category'] ?? 'N/A' }}</div>
                        </div>
                        <div class="col-md-3">
                            <div class="small text-muted">Location</div>
                            <div class="fw-semibold">{{ collect([$airbnbListing['city'] ?? null, $airbnbListing['country_code'] ?? null])->filter()->implode(', ') ?: 'N/A' }}</div>
                        </div>
                        <div class="col-12">
                            @if (! empty($selectedListing['airbnb_listing_id']))
                                <a href="https://www.airbnb.com/rooms/{{ $selectedListing['airbnb_listing_id'] }}"
                                   target="_blank" rel="noopener noreferrer"
                                   class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-box-arrow-up-right me-1"></i>View on Airbnb
                                </a>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="text-center py-4">
                        <div class="display-6 text-muted mb-2"><i class="bi bi-info-circle"></i></div>
                        <p class="mb-1">Select a listing to view Airbnb listing details from Beds24.</p>
                        <p class="small text-muted mb-0">This panel is handy when you want a quick summary without opening the raw API response.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
