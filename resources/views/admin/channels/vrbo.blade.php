@extends('layouts.admin.app')
@section('title', 'VRBO via Beds24')
@section('content')
@php
    $selectedAccountName = $selectedAccount?->name ?? 'No account selected';
@endphp

<div class="ch-page-header">
    <div>
        <div class="ch-breadcrumb">Channels</div>
        <h4>VRBO via Beds24</h4>
        <p class="ch-subtitle mb-0">
            Review VRBO bookings fetched from Beds24.
        </p>
    </div>
    <a href="{{ route('admin.channels.integrations') }}" class="btn btn-outline-primary">Beds24 integrations</a>
</div>

<div class="alert alert-info border-0 shadow-sm mb-3">
    <div class="d-flex flex-column flex-md-row justify-content-between gap-2">
        <div>
            <strong>How this page works:</strong>
            pick a Beds24 account and load bookings where the source is Vrbo/HomeAway.
        </div>
        <div class="small text-muted">
            Beds24 exposes Vrbo bookings through the standard bookings endpoint using the Vrbo source ID.
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.channels.vrbo') }}" class="row g-2 align-items-end">
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
            @if ($selectedBookingId)
                <input type="hidden" name="booking_id" value="{{ $selectedBookingId }}">
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
            <span class="badge text-bg-light">{{ count($bookings) }} Vrbo booking{{ count($bookings) === 1 ? '' : 's' }}</span>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white">
                <div class="fw-semibold">Vrbo bookings</div>
                <div class="small text-muted">Bookings Beds24 reports with source id 30.</div>
            </div>
            <div class="card-body">
                @if ($bookingsError)
                    <div class="alert alert-danger">{{ $bookingsError }}</div>
                @endif
                @forelse ($bookings as $booking)
                    <div class="border-bottom py-3 {{ $selectedBookingId === $booking['id'] ? 'bg-light rounded px-2' : '' }}">
                        <div class="d-flex justify-content-between gap-3 align-items-start">
                            <div>
                                <strong>{{ $booking['reference'] }}</strong>
                                <div class="small text-muted">Booking ID: {{ $booking['id'] }}</div>
                                <div class="small text-muted">Guest: {{ $booking['guest_name'] ?: 'Anonymous' }}</div>
                                <div class="small text-muted">
                                    Stay:
                                    {{ $booking['check_in'] ?? 'N/A' }} - {{ $booking['check_out'] ?? 'N/A' }}
                                </div>
                                <div class="small text-muted">Source: {{ $booking['api_source_text'] }}</div>
                            </div>
                            <a
                                href="{{ route('admin.channels.vrbo', array_filter([
                                    'account_id' => $selectedAccount?->id,
                                    'booking_id' => $booking['id'],
                                ], static fn ($value) => $value !== null && $value !== '')) }}"
                                class="btn btn-sm btn-outline-primary"
                            >
                                View
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-5">
                        <div class="display-6 text-muted mb-2"><i class="bi bi-house-door"></i></div>
                        <p class="mb-1">No Vrbo bookings returned yet.</p>
                        <p class="small text-muted mb-0">Select a connected Beds24 account and load data from the bookings endpoint.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white">
                <div class="fw-semibold">Selected booking details</div>
                <div class="small text-muted">A concise summary of the selected Vrbo booking.</div>
            </div>
            <div class="card-body">
                @if ($selectedBooking)
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="small text-muted">Reference</div>
                            <div class="fw-semibold">{{ $selectedBooking['reference'] }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="small text-muted">Booking ID</div>
                            <div class="fw-semibold">{{ $selectedBooking['id'] }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="small text-muted">Source</div>
                            <div class="fw-semibold">{{ $selectedBooking['api_source_text'] }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="small text-muted">Guest</div>
                            <div class="fw-semibold">{{ $selectedBooking['guest_name'] ?: 'Anonymous' }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="small text-muted">Email</div>
                            <div class="fw-semibold">{{ $selectedBooking['guest_email'] ?? 'N/A' }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="small text-muted">Room ID</div>
                            <div class="fw-semibold">{{ $selectedBooking['room_id'] ?? 'N/A' }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="small text-muted">Check-in</div>
                            <div class="fw-semibold">{{ $selectedBooking['check_in'] ?? 'N/A' }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="small text-muted">Check-out</div>
                            <div class="fw-semibold">{{ $selectedBooking['check_out'] ?? 'N/A' }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="small text-muted">Status</div>
                            <div class="fw-semibold">{{ $selectedBooking['status'] }}</div>
                        </div>
                    </div>
                @else
                    <div class="text-center py-4">
                        <div class="display-6 text-muted mb-2"><i class="bi bi-info-circle"></i></div>
                        <p class="mb-1">Choose a booking to inspect the fetched Vrbo data.</p>
                        <p class="small text-muted mb-0">The page defaults to the latest Vrbo booking if one is available.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
