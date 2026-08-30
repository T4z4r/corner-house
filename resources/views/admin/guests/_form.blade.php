@php $guest = $guest ?? null; @endphp

@csrf

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label" for="first_name">First name *</label>
        <input type="text" class="form-control @error('first_name') is-invalid @enderror" id="first_name" name="first_name"
               value="{{ old('first_name', $guest?->first_name) }}" placeholder="e.g. Jane" required>
        @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="last_name">Last name *</label>
        <input type="text" class="form-control @error('last_name') is-invalid @enderror" id="last_name" name="last_name"
               value="{{ old('last_name', $guest?->last_name) }}" placeholder="e.g. Smith" required>
        @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="email">Email</label>
        <input type="email" class="form-control" id="email" name="email" value="{{ old('email', $guest?->email) }}" placeholder="guest@example.com">
    </div>
    <div class="col-md-6">
        <label class="form-label" for="phone">Phone</label>
        <input type="text" class="form-control" id="phone" name="phone" value="{{ old('phone', $guest?->phone) }}" placeholder="+44 1234 567890">
    </div>
    <div class="col-md-4">
        <label class="form-label" for="country">Country</label>
        <input type="text" class="form-control" id="country" name="country" maxlength="2"
               value="{{ old('country', $guest?->country) }}" placeholder="GB">
    </div>
    <div class="col-md-4">
        <label class="form-label" for="language">Language</label>
        <input type="text" class="form-control" id="language" name="language" maxlength="5"
               value="{{ old('language', $guest?->language) }}" placeholder="en">
    </div>
    <div class="col-md-4">
        <label class="form-label" for="status">Status</label>
        <select class="form-select" id="status" name="status">
            @foreach (['active', 'inactive', 'blacklisted'] as $status)
                <option value="{{ $status }}" @selected(old('status', $guest?->status) === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-12">
        <label class="form-label" for="source">Source</label>
        <input type="text" class="form-control" id="source" name="source" placeholder="direct"
               value="{{ old('source', $guest?->source) }}">
    </div>
    <div class="col-12">
        <label class="form-label" for="notes">Notes</label>
        <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Special requests, preferences, or administrative notes...">{{ old('notes', $guest?->notes) }}</textarea>
    </div>
</div>
