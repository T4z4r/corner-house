@php $property = $property ?? null; @endphp

@csrf

<div class="row g-3">
    <div class="col-md-8">
        <label class="form-label" for="name">Name *</label>
        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name"
               value="{{ old('name', $property?->name) }}" placeholder="e.g. Corner House" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="status">Status</label>
        <select class="form-select" id="status" name="status">
            @foreach (['active', 'inactive', 'maintenance'] as $status)
                <option value="{{ $status }}" @selected(old('status', $property?->status) === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-12">
        <label class="form-label" for="short_description">Short description</label>
        <input type="text" class="form-control" id="short_description" name="short_description" maxlength="500"
               value="{{ old('short_description', $property?->short_description) }}" placeholder="A short summary shown in listings">
    </div>
    <div class="col-12">
        <label class="form-label" for="description">Full description</label>
        <textarea class="form-control" id="description" name="description" rows="4" placeholder="A full description of the property...">{{ old('description', $property?->description) }}</textarea>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="address_line_1">Address line 1</label>
        <input type="text" class="form-control" id="address_line_1" name="address_line_1"
               value="{{ old('address_line_1', $property?->address_line_1) }}" placeholder="e.g. 12 High Street">
    </div>
    <div class="col-md-6">
        <label class="form-label" for="address_line_2">Address line 2</label>
        <input type="text" class="form-control" id="address_line_2" name="address_line_2"
               value="{{ old('address_line_2', $property?->address_line_2) }}" placeholder="Apt, floor, landmark (optional)">
    </div>
    <div class="col-md-4">
        <label class="form-label" for="city">City</label>
        <input type="text" class="form-control" id="city" name="city" value="{{ old('city', $property?->city) }}" placeholder="e.g. Edinburgh">
    </div>
    <div class="col-md-4">
        <label class="form-label" for="postcode">Postcode</label>
        <input type="text" class="form-control" id="postcode" name="postcode" value="{{ old('postcode', $property?->postcode) }}" placeholder="e.g. EH1 1AA">
    </div>
    <div class="col-md-2">
        <label class="form-label" for="country">Country</label>
        <input type="text" class="form-control" id="country" name="country" maxlength="2" value="{{ old('country', $property?->country) }}" placeholder="GB">
    </div>
    <div class="col-md-2">
        <label class="form-label" for="currency">Currency</label>
        <input type="text" class="form-control" id="currency" name="currency" maxlength="3"
               value="{{ old('currency', $property?->currency ?? 'GBP') }}" placeholder="GBP">
    </div>
    <div class="col-md-3">
        <label class="form-label" for="capacity">Capacity</label>
        <input type="number" class="form-control" id="capacity" name="capacity" min="1" value="{{ old('capacity', $property?->capacity ?? 4) }}">
    </div>
    <div class="col-md-3">
        <label class="form-label" for="bedrooms">Bedrooms</label>
        <input type="number" class="form-control" id="bedrooms" name="bedrooms" min="0" value="{{ old('bedrooms', $property?->bedrooms ?? 2) }}">
    </div>
    <div class="col-md-3">
        <label class="form-label" for="bathrooms">Bathrooms</label>
        <input type="number" class="form-control" id="bathrooms" name="bathrooms" min="0" value="{{ old('bathrooms', $property?->bathrooms ?? 1) }}">
    </div>
    <div class="col-md-3">
        <label class="form-label" for="latitude">Latitude</label>
        <input type="text" class="form-control" id="latitude" name="latitude" step="any" value="{{ old('latitude', $property?->latitude) }}" placeholder="e.g. 55.9533">
    </div>
    <div class="col-md-3">
        <label class="form-label" for="longitude">Longitude</label>
        <input type="text" class="form-control" id="longitude" name="longitude" step="any" value="{{ old('longitude', $property?->longitude) }}" placeholder="e.g. -3.1883">
    </div>
    <div class="col-12">
        <label class="form-label d-block">Amenities</label>
        <div class="row">
            @forelse ($amenities as $amenity)
                <div class="col-md-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="amenity_ids[]" value="{{ $amenity->id }}"
                               id="amenity_{{ $amenity->id }}"
                               @checked(in_array($amenity->id, old('amenity_ids', $property?->amenities?->pluck('id')->all() ?? [])))>
                        <label class="form-check-label" for="amenity_{{ $amenity->id }}">{{ $amenity->name }}</label>
                    </div>
                </div>
            @empty
                <div class="text-muted small">No amenities defined yet.</div>
            @endforelse
        </div>
    </div>

    <div class="col-12">
        <hr class="my-3">
        <h6 class="mb-3"><i class="bi bi-house-exclamation me-2"></i>House Rules</h6>
    </div>

    <div class="col-md-4">
        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" name="smoking_allowed" value="1" id="smoking_allowed"
                   @checked(old('smoking_allowed', $property?->smoking_allowed ?? false))>
            <label class="form-check-label" for="smoking_allowed">Smoking in shared areas</label>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" name="children_allowed" value="1" id="children_allowed"
                   @checked(old('children_allowed', $property?->children_allowed ?? true))>
            <label class="form-check-label" for="children_allowed">Children allowed</label>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" name="parties_allowed" value="1" id="parties_allowed"
                   @checked(old('parties_allowed', $property?->parties_allowed ?? false))>
            <label class="form-check-label" for="parties_allowed">Parties / events allowed</label>
        </div>
    </div>

    <div class="col-md-4">
        <label class="form-label">Pets allowed</label>
        <select name="pets_allowed" class="form-select" id="pets_allowed">
            @foreach (['no' => 'No', 'upon_request' => 'Upon request', 'yes' => 'Yes'] as $value => $label)
                <option value="{{ $value }}" @selected(old('pets_allowed', $property?->pets_allowed ?? 'no') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-8"></div>

    <div class="col-md-3">
        <label class="form-label">Check-in from</label>
        <input type="time" class="form-control" name="check_in_from" value="{{ old('check_in_from', $property?->check_in_from ?? '15:00') }}">
    </div>
    <div class="col-md-3">
        <label class="form-label">Check-in until</label>
        <input type="time" class="form-control" name="check_in_until" value="{{ old('check_in_until', $property?->check_in_until ?? '18:00') }}">
    </div>
    <div class="col-md-3">
        <label class="form-label">Check-out from</label>
        <input type="time" class="form-control" name="check_out_from" value="{{ old('check_out_from', $property?->check_out_from ?? '08:00') }}">
    </div>
    <div class="col-md-3">
        <label class="form-label">Check-out until</label>
        <input type="time" class="form-control" name="check_out_until" value="{{ old('check_out_until', $property?->check_out_until ?? '11:00') }}">
    </div>

    <div class="col-12 mt-2">
        <label class="form-label">Custom rules <span class="text-muted">(optional)</span></label>
        <textarea class="form-control" name="custom_rules" rows="3" placeholder="e.g. Quiet hours after 22:00, No shoes indoors, Respect the neighbours...">{{ old('custom_rules', $property?->custom_rules) }}</textarea>
        <div class="form-text">One rule per line, or free-form text.</div>
    </div>
</div>
