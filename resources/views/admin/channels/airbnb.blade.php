@extends('layouts.admin.app')
@section('title', 'Airbnb via Beds24')
@push('styles')
<style>
    .ch-stat-card {
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }

    .ch-stat-card:hover {
        transform: translateY(-1px);
        box-shadow: var(--ch-shadow) !important;
    }

    .ch-stat-icon {
        width: 3rem;
        height: 3rem;
        border-radius: 0.75rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        flex-shrink: 0;
    }

    .ch-stat-icon-primary { background: var(--ch-primary-soft); color: var(--ch-primary); }
    .ch-stat-icon-success { background: rgba(31, 111, 67, 0.12); color: var(--ch-primary); }
    .ch-stat-icon-accent { background: rgba(201, 162, 39, 0.15); color: var(--ch-accent); }
    .ch-stat-icon-info { background: rgba(13, 110, 253, 0.1); color: #0d6efd; }
    .ch-stat-icon-muted { background: #eef1f4; color: #6b7280; }

    .ch-stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        line-height: 1.1;
        color: var(--ch-ink);
    }

    .ch-stat-label {
        font-size: 0.82rem;
        font-weight: 600;
        color: var(--ch-muted);
    }

    .ch-stat-card .min-w-0 {
        min-width: 0;
    }

    .ch-view-tabs .nav-link {
        font-weight: 600;
        color: var(--ch-muted);
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
    }

    .ch-view-tabs .nav-link.active {
        color: var(--ch-primary);
        border-bottom-color: var(--ch-primary);
    }

    .ch-view-tabs .nav-link .badge {
        font-size: 0.68rem;
    }
</style>
@endpush
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

<div class="row g-3 mb-3">
    @php
        $statCards = [
            [
                'label' => 'Airbnb users',
                'value' => $stats['user_count'],
                'icon' => 'bi-people',
                'hint' => 'Loaded from Beds24',
                'tone' => 'primary',
            ],
            [
                'label' => 'Listings',
                'value' => $stats['listing_count'],
                'icon' => 'bi-house-heart',
                'hint' => $stats['enabled_count'].' enabled',
                'tone' => 'success',
            ],
            [
                'label' => 'Linked to Beds24',
                'value' => $stats['linked_count'],
                'icon' => 'bi-link-45deg',
                'hint' => 'Expose a room ID',
                'tone' => 'accent',
            ],
            [
                'label' => 'Reviews',
                'value' => $stats['review_count'],
                'icon' => 'bi-chat-square-text',
                'hint' => $stats['review_avg_score'] !== null ? 'Avg '.$stats['review_avg_score'].'/10' : 'For selected room',
                'tone' => 'info',
            ],
            [
                'label' => 'Account mappings',
                'value' => $stats['mappings'],
                'icon' => 'bi-diagram-3',
                'hint' => $selectedAccount ? $selectedAccountName : 'No account',
                'tone' => 'muted',
            ],
        ];
    @endphp
    @foreach ($statCards as $card)
        <div class="col-6 col-md-4 col-xl">
            <div class="card border-0 shadow-sm ch-stat-card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="ch-stat-icon ch-stat-icon-{{ $card['tone'] }}">
                        <i class="bi {{ $card['icon'] }}"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="ch-stat-value">{{ $card['value'] }}</div>
                        <div class="ch-stat-label">{{ $card['label'] }}</div>
                        <div class="small text-muted">{{ $card['hint'] }}</div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<ul class="nav nav-tabs ch-view-tabs mb-3" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="tab-users" data-bs-toggle="tab" data-bs-target="#pane-users" type="button" role="tab">
            <i class="bi bi-people me-1"></i>Users
            <span class="badge text-bg-secondary ms-1">{{ $stats['user_count'] }}</span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="tab-listings" data-bs-toggle="tab" data-bs-target="#pane-listings" type="button" role="tab">
            <i class="bi bi-house-heart me-1"></i>Listings
            <span class="badge text-bg-secondary ms-1">{{ $stats['listing_count'] }}</span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="tab-reviews" data-bs-toggle="tab" data-bs-target="#pane-reviews" type="button" role="tab">
            <i class="bi bi-chat-square-text me-1"></i>Reviews
            <span class="badge text-bg-secondary ms-1">{{ $stats['review_count'] }}</span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="tab-actions" data-bs-toggle="tab" data-bs-target="#pane-actions" type="button" role="tab">
            <i class="bi bi-arrow-left-right me-1"></i>Send to Beds24
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="tab-details" data-bs-toggle="tab" data-bs-target="#pane-details" type="button" role="tab">
            <i class="bi bi-card-text me-1"></i>Listing details
        </button>
    </li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="pane-users" role="tabpanel" aria-labelledby="tab-users">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <div class="fw-semibold">Airbnb users</div>
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

    <div class="tab-pane fade" id="pane-listings" role="tabpanel" aria-labelledby="tab-listings">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex flex-wrap justify-content-between gap-2 align-items-center">
                <div>
                    <div class="fw-semibold">Airbnb listings</div>
                    <div class="small text-muted">Open a listing to inspect Beds24 room details.</div>
                </div>
                @if ($stats['linked_count'] > 0 && $stats['listing_count'] > 0)
                    <span class="badge text-bg-success">{{ $stats['linked_count'] }} of {{ $stats['listing_count'] }} linked</span>
                @endif
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

    <div class="tab-pane fade" id="pane-reviews" role="tabpanel" aria-labelledby="tab-reviews">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex flex-wrap justify-content-between gap-2 align-items-center">
                <div>
                    <div class="fw-semibold">Airbnb reviews</div>
                    <div class="small text-muted">Reviews appear when a listing exposes a Beds24 room ID.</div>
                </div>
                @if ($selectedRoomId)
                    <span class="badge text-bg-light">Room ID: {{ $selectedRoomId }}</span>
                @endif
            </div>
            <div class="card-body">
                @if ($reviewsError)
                    <div class="alert alert-danger">{{ $reviewsError }}</div>
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

    <div class="tab-pane fade" id="pane-actions" role="tabpanel" aria-labelledby="tab-actions">
        <div class="card border-0 shadow-sm">
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
    </div>

    <div class="tab-pane fade" id="pane-details" role="tabpanel" aria-labelledby="tab-details">
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
