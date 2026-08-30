@extends('layouts.admin.app')

@section('title', 'House Rules')

@section('content')
    @if (! $property)
        <div class="alert alert-warning border-0 shadow-sm mb-4">
            No property record exists yet. Create a property first, then return here to configure house rules.
        </div>
    @endif

    <div class="ch-page-header">
        <div>
            <div class="ch-breadcrumb"><a href="{{ route('admin.website.index') }}">Website</a> / House Rules</div>
            <h4>House Rules</h4>
            <p class="ch-subtitle">Rules shown to guests on the website and booking pages</p>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <form method="POST" action="{{ route('admin.website.house-rules.update') }}">
                @csrf
                @method('PUT')

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white"><h6 class="mb-0">Guest Policies</h6></div>
                    <div class="card-body">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Pets</label>
                                <select name="pets_allowed" class="form-select">
                                    <option value="no" @selected(($property?->pets_allowed ?? 'no') === 'no')>Not allowed</option>
                                    <option value="upon_request" @selected(($property?->pets_allowed ?? 'no') === 'upon_request')>Upon request</option>
                                    <option value="yes" @selected(($property?->pets_allowed ?? 'no') === 'yes')>Allowed</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Smoking</label>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="smoking_allowed" value="1" {{ ($property?->smoking_allowed ?? false) ? 'checked' : '' }}>
                                    <label class="form-check-label">Smoking is permitted</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Children</label>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="children_allowed" value="1" {{ ($property?->children_allowed ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label">Children are welcome</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Parties / Events</label>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="parties_allowed" value="1" {{ ($property?->parties_allowed ?? false) ? 'checked' : '' }}>
                                    <label class="form-check-label">Parties and events are permitted</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white"><h6 class="mb-0">Check-in & Check-out</h6></div>
                    <div class="card-body">
                        <div class="row g-4">
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Check-in from</label>
                                <input type="time" name="check_in_from" class="form-control" value="{{ $property?->check_in_from ?? '15:00' }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Check-in until</label>
                                <input type="time" name="check_in_until" class="form-control" value="{{ $property?->check_in_until ?? '18:00' }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Check-out from</label>
                                <input type="time" name="check_out_from" class="form-control" value="{{ $property?->check_out_from ?? '08:00' }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Check-out until</label>
                                <input type="time" name="check_out_until" class="form-control" value="{{ $property?->check_out_until ?? '12:00' }}">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white"><h6 class="mb-0">Custom Rules</h6></div>
                    <div class="card-body">
                        <p class="text-muted small mb-2">Additional rules shown on the property page and booking details.</p>
                        <textarea name="custom_rules" class="form-control" rows="4" placeholder="e.g. No shoes indoors. Quiet hours after 10pm...">{{ $property?->custom_rules }}</textarea>
                    </div>
                </div>

                <button type="submit" class="btn btn-ch-primary" @disabled(! $property)><i class="bi bi-check-lg me-1"></i>Save house rules</button>
            </form>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white"><h6 class="mb-0">Preview</h6></div>
                <div class="card-body">
                    @if ($property)
                        <div class="mb-3">
                            <div class="ch-label">Pets</div>
                            <span class="ch-badge ch-badge-muted">{{ match($property->pets_allowed ?? 'no') { 'yes' => 'Allowed', 'upon_request' => 'Upon request', default => 'Not allowed' } }}</span>
                        </div>
                        <div class="mb-3">
                            <div class="ch-label">Smoking</div>
                            <span class="ch-badge {{ ($property->smoking_allowed ?? false) ? 'ch-badge-success' : 'ch-badge-muted' }}">
                                <span class="dot"></span>{{ ($property->smoking_allowed ?? false) ? 'Allowed' : 'Not allowed' }}
                            </span>
                        </div>
                        <div class="mb-3">
                            <div class="ch-label">Children</div>
                            <span class="ch-badge {{ ($property->children_allowed ?? true) ? 'ch-badge-success' : 'ch-badge-muted' }}">
                                <span class="dot"></span>{{ ($property->children_allowed ?? true) ? 'Welcome' : 'Not allowed' }}
                            </span>
                        </div>
                        <div class="mb-3">
                            <div class="ch-label">Parties</div>
                            <span class="ch-badge {{ ($property->parties_allowed ?? false) ? 'ch-badge-success' : 'ch-badge-muted' }}">
                                <span class="dot"></span>{{ ($property->parties_allowed ?? false) ? 'Allowed' : 'Not allowed' }}
                            </span>
                        </div>
                        <hr>
                        <div class="mb-2">
                            <div class="ch-label">Check-in</div>
                            <div class="fw-semibold">{{ $property->check_in_from ?? '15:00' }} - {{ $property->check_in_until ?? '18:00' }}</div>
                        </div>
                        <div class="mb-2">
                            <div class="ch-label">Check-out</div>
                            <div class="fw-semibold">{{ $property->check_out_from ?? '08:00' }} - {{ $property->check_out_until ?? '12:00' }}</div>
                        </div>
                        @if ($property->custom_rules)
                            <hr>
                            <div class="ch-label mb-1">Custom rules</div>
                            <div class="text-muted small" style="white-space: pre-line;">{{ $property->custom_rules }}</div>
                        @endif
                    @else
                        <div class="calendar-empty">
                            <div class="display-6 mb-2 text-muted"><i class="bi bi-house-x"></i></div>
                            <p class="mb-1">No property found yet.</p>
                            <p class="small mb-0">Create a property before configuring or previewing house rules.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
