@extends('layouts.admin.app')

@section('title', 'New Reservation')

@section('content')
    <div class="ch-page-header">
        <div>
            <div class="ch-breadcrumb"><a href="{{ route('admin.reservations.index') }}">Bookings</a> / New</div>
            <h4>New Reservation</h4>
            <p class="ch-subtitle">Manually create a booking for a room</p>
        </div>
    </div>

    <div class="card border-0 shadow-sm ch-form-card">
        <div class="ch-card-header">
            <h6><i class="bi bi-journal-plus"></i>Booking details</h6>
                            <p>Choose a room, dates and lead guest for the booking</p>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.reservations.store') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="room_id">Room *</label>
                        <select class="form-select" id="room_id" name="room_id" required>
                            <option value="">Select a room...</option>
                            @foreach ($rooms as $room)
                                <option value="{{ $room->id }}">{{ $room->property->name }} — {{ $room->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="check_in">Check-in *</label>
                        <input type="date" class="form-control" id="check_in" name="check_in" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="check_out">Check-out *</label>
                        <input type="date" class="form-control" id="check_out" name="check_out" required>
                    </div>
                    <div class="col-12"><hr></div>
                    <div class="col-md-3">
                        <label class="form-label" for="guests_count">Guests</label>
                        <input type="number" class="form-control" id="guests_count" name="guests_count" min="1" value="2">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label" for="guest_first_name">Lead guest first name</label>
                        <input type="text" class="form-control" id="guest_first_name" name="guest_first_name" placeholder="e.g. Jane">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="guest_last_name">Lead guest last name</label>
                        <input type="text" class="form-control" id="guest_last_name" name="guest_last_name" placeholder="e.g. Smith">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="guest_email">Email</label>
                        <input type="email" class="form-control" id="guest_email" name="guest_email" placeholder="guest@example.com">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="notes">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Special requests, deposit details, or internal notes..."></textarea>
                    </div>
                </div>
                <div class="ch-form-actions">
                    <button class="btn btn-ch-primary"><i class="bi bi-check-lg me-1"></i>Create booking</button>
                    <a href="{{ route('admin.reservations.index') }}" class="btn btn-light">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
