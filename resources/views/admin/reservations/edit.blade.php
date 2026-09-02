@extends('layouts.admin.app')

@section('title', 'Edit Reservation')

@section('content')
    <div class="ch-page-header">
        <div>
            <div class="ch-breadcrumb"><a href="{{ route('admin.reservations.index') }}">Bookings</a> / <a href="{{ route('admin.reservations.show', $reservation) }}">{{ $reservation->reference }}</a> / Edit</div>
            <h4>Edit Reservation</h4>
            <p class="ch-subtitle">{{ $reservation->reference }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.reservations.show', $reservation) }}" class="btn btn-light"><i class="bi bi-eye me-1"></i>View</a>
            @can('reservations.delete')
                <form method="POST" action="{{ route('admin.reservations.destroy', $reservation) }}" onsubmit="return confirm('Delete this booking permanently?');">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-outline-danger"><i class="bi bi-trash me-1"></i>Delete</button>
                </form>
            @endcan
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card border-0 shadow-sm ch-form-card">
        <div class="ch-card-header">
            <h6><i class="bi bi-journal-text"></i>Booking details</h6>
            <p>Update the room, dates or lead guest for the booking</p>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.reservations.update', $reservation) }}">
                @csrf
                @method('PUT')
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="room_id">Room *</label>
                        <select class="form-select" id="room_id" name="room_id" required>
                            <option value="">Select a room...</option>
                            @foreach ($rooms as $room)
                                <option value="{{ $room->id }}" @selected(old('room_id', $reservation->room_id) == $room->id)>{{ $room->property->name }} — {{ $room->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="check_in">Check-in *</label>
                        <input type="date" class="form-control" id="check_in" name="check_in" value="{{ old('check_in', $reservation->check_in->format('Y-m-d')) }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="check_out">Check-out *</label>
                        <input type="date" class="form-control" id="check_out" name="check_out" value="{{ old('check_out', $reservation->check_out->format('Y-m-d')) }}" required>
                    </div>
                    <div class="col-12"><hr></div>
                    <div class="col-md-3">
                        <label class="form-label" for="guests_count">Guests</label>
                        <input type="number" class="form-control" id="guests_count" name="guests_count" min="1" value="{{ old('guests_count', $reservation->guests_count) }}">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label" for="guest_first_name">Lead guest first name</label>
                        <input type="text" class="form-control" id="guest_first_name" name="guest_first_name" value="{{ old('guest_first_name', $reservation->guest?->first_name) }}" placeholder="e.g. Jane">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="guest_last_name">Lead guest last name</label>
                        <input type="text" class="form-control" id="guest_last_name" name="guest_last_name" value="{{ old('guest_last_name', $reservation->guest?->last_name) }}" placeholder="e.g. Smith">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="guest_email">Email</label>
                        <input type="email" class="form-control" id="guest_email" name="guest_email" value="{{ old('guest_email', $reservation->guest?->email) }}" placeholder="guest@example.com">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="notes">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Special requests, deposit details, or internal notes...">{{ old('notes', $reservation->notes) }}</textarea>
                    </div>
                </div>
                <div class="ch-form-actions">
                    <button class="btn btn-ch-primary"><i class="bi bi-check-lg me-1"></i>Save changes</button>
                    <a href="{{ route('admin.reservations.show', $reservation) }}" class="btn btn-light">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection