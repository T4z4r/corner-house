@extends('layouts.admin.app')
@section('title', 'Booking.com via Beds24')
@section('content')
@php
    $selectedAccountName = $selectedAccount?->name ?? 'No account selected';
    $selectedRoom = collect($beds24Rooms)->firstWhere('beds24_room_id', $selectedRoomId);
    $selectedRoomLabel = $selectedRoom['label'] ?? 'No room selected';
@endphp

<div class="ch-page-header">
    <div>
        <div class="ch-breadcrumb">Channels</div>
        <h4>Booking.com via Beds24</h4>
        <p class="ch-subtitle mb-0">
            Browse Booking.com review data fetched live through Beds24.
        </p>
    </div>
    <a href="{{ route('admin.channels.integrations') }}" class="btn btn-outline-primary">Beds24 integrations</a>
</div>

<div class="alert alert-info border-0 shadow-sm mb-3">
    <div class="d-flex flex-column flex-md-row justify-content-between gap-2">
        <div>
            <strong>How this page works:</strong>
            pick a Beds24 account, select a mapped room, then load Booking.com review data for that room.
        </div>
        <div class="small text-muted">
            This page uses Beds24 as the source of truth for Booking.com channel data.
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.channels.booking') }}" class="row g-2 align-items-end">
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
            <div class="col-lg-5">
                <label class="form-label small text-muted">Beds24 room</label>
                <select name="room_id" class="form-select">
                    @forelse ($beds24Rooms as $beds24Room)
                        <option value="{{ $beds24Room['beds24_room_id'] }}" @selected($selectedRoomId === $beds24Room['beds24_room_id'])>
                            {{ $beds24Room['label'] }}
                        </option>
                    @empty
                        <option value="">No Beds24 rooms mapped yet</option>
                    @endforelse
                </select>
            </div>
            <div class="col-lg-2">
                <button class="btn btn-ch-primary w-100" type="submit">Load data</button>
            </div>
        </form>

        <div class="d-flex flex-wrap gap-2 align-items-center mt-3">
            <span class="badge text-bg-{{ $selectedAccount ? 'success' : 'secondary' }}">
                {{ $selectedAccount ? 'Connected' : 'No account selected' }}
            </span>
            <span class="small text-muted">{{ $selectedAccountName }}</span>
            <span class="badge text-bg-light">{{ $selectedRoomLabel }}</span>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white">
                <div class="fw-semibold">Booking.com reviews</div>
                <div class="small text-muted">Review data returned from Beds24 for the selected room.</div>
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
                        $content = $review['content'] ?? $review;
                        $reviewer = $review['reviewer'] ?? [];
                        $scoring = $review['scoring'] ?? [];
                    @endphp
                    <div class="border-bottom py-3">
                        <div class="d-flex justify-content-between gap-3">
                            <div>
                                <strong>{{ $content['headline'] ?? $content['title'] ?? 'Booking.com review' }}</strong>
                                <div class="small text-muted">Review ID: {{ $review['review_id'] ?? $review['id'] ?? 'N/A' }}</div>
                                <div class="small text-muted">Reviewer: {{ $reviewer['name'] ?? $content['reviewer_name'] ?? 'Anonymous' }}</div>
                            </div>
                            <span class="badge text-bg-secondary">{{ $scoring['review_score'] ?? $review['review_score'] ?? 'N/A' }}</span>
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
                            {{ $selectedRoomId ? 'No reviews returned for this room.' : 'Select a room to load reviews.' }}
                        </p>
                        <p class="small text-muted mb-0">Beds24 only returns data when the Booking.com room is connected.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white">
                <div class="fw-semibold">Selected room details</div>
                <div class="small text-muted">A concise summary of the room and the most recent review data.</div>
            </div>
            <div class="card-body">
                @if ($selectedRoomId)
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="small text-muted">Room</div>
                            <div class="fw-semibold">{{ $selectedRoomLabel }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="small text-muted">Beds24 room ID</div>
                            <div class="fw-semibold">{{ $selectedRoomId }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="small text-muted">Source</div>
                            <div class="fw-semibold">Booking.com via Beds24</div>
                        </div>
                    </div>
                @else
                    <div class="text-center py-4">
                        <div class="display-6 text-muted mb-2"><i class="bi bi-info-circle"></i></div>
                        <p class="mb-1">Choose a mapped room to load Booking.com data from Beds24.</p>
                        <p class="small text-muted mb-0">This keeps Booking.com data inside the same channel workflow as Airbnb.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mt-3">
    <div class="card-header bg-white d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
        <div>
            <div class="fw-semibold">Booking.com rate mapping</div>
            <div class="small text-muted">Rooms, rates and occupancy pricing stored from Beds24's getmapping feed.</div>
        </div>
        @can('channels.configure')
            @if ($selectedAccount)
                <form method="POST" action="{{ route('admin.channels.booking-mapping.sync') }}" class="d-flex gap-2 align-items-end">
                    @csrf
                    <input type="hidden" name="account_id" value="{{ $selectedAccount->id }}">
                    <div>
                        <label class="form-label small text-muted mb-1" for="propid">Beds24 property ID</label>
                        <input type="text" class="form-control form-control-sm w-auto" id="propid" name="propid"
                               value="{{ $mappedPropertyIds->first() }}" placeholder="e.g. 351393">
                    </div>
                    <button class="btn btn-sm btn-ch-primary text-nowrap">Fetch &amp; save mapping</button>
                </form>
            @endif
        @endcan
    </div>
    <div class="card-body p-0">
        @if ($rateMaps->isEmpty())
            <div class="text-center py-5">
                <div class="display-6 text-muted mb-2"><i class="bi bi-diagram-2"></i></div>
                <p class="mb-1">{{ $selectedAccount ? 'No rate mapping saved for this account yet.' : 'Select a Beds24 account to sync its rate mapping.' }}</p>
                <p class="small text-muted mb-0">Fetching stores each room's rates and occupancy pricing percentages.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Property</th>
                            <th>Room</th>
                            <th>Rate</th>
                            <th>Occupancy %</th>
                            <th>Relation</th>
                            <th>Synced</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rateMaps as $map)
                            @foreach ($map->rates as $rate)
                                <tr>
                                    <td>{{ $map->hotel_name ?: $map->external_property_id }}</td>
                                    <td>
                                        {{ $rate->room_name ?: $rate->external_room_id }}
                                        <div class="small text-muted">Room id: {{ $rate->external_room_id }}</div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ $rate->rate_name }}</div>
                                        <div class="small text-muted">Rate id: {{ $rate->external_rate_id }} &middot; {{ $rate->is_child_rate ? 'child' : 'base' }}</div>
                                    </td>
                                    <td>
                                        @forelse ($rate->occupancy ?? [] as $occupancy)
                                            <span class="ch-badge ch-badge-muted">{{ $occupancy['persons'] ?? '?' }}p &middot; {{ $occupancy['percentage'] ?? '?' }}%</span>
                                        @empty
                                            <span class="text-muted small">—</span>
                                        @endforelse
                                    </td>
                                    <td>
                                        @if ($rate->parent_rate_id)
                                            <span class="small text-muted">parent {{ $rate->parent_rate_id }} @ {{ $rate->percentage }}%</span>
                                        @else
                                            <span class="text-muted small">base rate</span>
                                        @endif
                                    </td>
                                    <td class="text-nowrap small">{{ $map->synced_at?->format('d M Y H:i') ?? '—' }}</td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
