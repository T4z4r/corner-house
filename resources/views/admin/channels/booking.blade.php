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
@endsection
