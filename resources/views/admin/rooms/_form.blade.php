@php
    $room = $room ?? null;
@endphp

@csrf

<div class="row g-3">
    <div class="col-md-8">
        <label class="form-label" for="name">Room name *</label>
        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name"
               value="{{ old('name', $room?->name) }}" placeholder="e.g. The Garden Room" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="type">Type</label>
        <input type="text" class="form-control" id="type" name="type" placeholder="En-suite"
               value="{{ old('type', $room?->type) }}">
    </div>

    <div class="col-md-4">
        <label class="form-label" for="status">Status</label>
        <select class="form-select" id="status" name="status">
            @foreach (['active', 'inactive', 'maintenance'] as $status)
                <option value="{{ $status }}" @selected(old('status', $room?->status) === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="base_rate">Base rate / night *</label>
        <div class="input-group">
            <span class="input-group-text">&pound;</span>
            <input type="number" step="0.01" class="form-control" id="base_rate" name="base_rate" required
                   value="{{ old('base_rate', $room?->base_rate) }}" placeholder="e.g. 95.00">
        </div>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="is_private">Listing type</label>
        <select class="form-select" id="is_private" name="is_private">
            <option value="1" @selected((bool) old('is_private', $room?->is_private))>Whole unit</option>
            <option value="0" @selected(! (bool) old('is_private', $room?->is_private))>Shared</option>
        </select>
    </div>

    <div class="col-md-3">
        <label class="form-label" for="capacity">Guest capacity</label>
        <input type="number" class="form-control" id="capacity" name="capacity" min="1"
               value="{{ old('capacity', $room?->capacity ?? 1) }}">
    </div>
    <div class="col-md-3">
        <label class="form-label" for="sleeps">Sleeps</label>
        <input type="number" class="form-control" id="sleeps" name="sleeps" min="1"
               value="{{ old('sleeps', $room?->sleeps ?? 1) }}">
    </div>
    <div class="col-md-3">
        <label class="form-label" for="bedrooms">Bedrooms</label>
        <input type="number" class="form-control" id="bedrooms" name="bedrooms" min="0"
               value="{{ old('bedrooms', $room?->bedrooms ?? 1) }}">
    </div>
    <div class="col-md-3">
        <label class="form-label" for="bathrooms">Bathrooms</label>
        <input type="number" class="form-control" id="bathrooms" name="bathrooms" min="0"
               value="{{ old('bathrooms', $room?->bathrooms ?? 1) }}">
    </div>

    <div class="col-md-4">
        <label class="form-label" for="min_stay">Minimum stay (nights)</label>
        <input type="number" class="form-control" id="min_stay" name="min_stay" min="1"
               value="{{ old('min_stay', $room?->min_stay ?? 1) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label" for="max_stay">Maximum stay (nights)</label>
        <input type="number" class="form-control" id="max_stay" name="max_stay" min="1"
               value="{{ old('max_stay', $room?->max_stay) }}">
    </div>

    <div class="col-12">
        <label class="form-label" for="description">Description</label>
        <textarea class="form-control" id="description" name="description" rows="4" placeholder="Room details, features, and anything worth mentioning...">{{ old('description', $room?->description) }}</textarea>
    </div>

    <div class="col-12">
        <label class="form-label">Images</label>
        @if ($room && $room->exists)
            @if ($room->images->isNotEmpty())
                <div class="row g-2 mb-3" id="existingImages">
                    @foreach ($room->images->sortBy('sort_order') as $image)
                        <div class="col-auto position-relative" id="img-{{ $image->id }}">
                            <img src="{{ asset('storage/'.$image->path) }}" alt="{{ $image->alt }}" class="rounded border" style="height: 80px; object-fit: cover;">
                            @if ($image->is_primary)
                                <span class="position-absolute top-0 start-0 badge bg-primary m-1" style="font-size:0.65rem">Primary</span>
                            @endif
                            <button type="button" class="btn btn-sm btn-outline-danger position-absolute top-0 end-0 m-1 dz-delete-existing" data-image-id="{{ $image->id }}" title="Remove" style="padding: 0 5px; font-size: 0.7rem;">&times;</button>
                        </div>
                    @endforeach
                </div>
            @endif
            <div class="dz-upload-area" id="dz-room-images" data-room-id="{{ $room->id }}">
                <div class="dz-dropzone rounded border border-2 border-dashed p-4 text-center" style="cursor:pointer; min-height: 80px; transition: all 0.2s;">
                    <i class="bi bi-cloud-arrow-up fs-3 text-muted"></i>
                    <div class="small text-muted mt-1">Drop images here or click to browse</div>
                    <div class="small text-muted">JPG, PNG, WebP — up to 5MB each</div>
                </div>
            </div>
        @else
            <div class="text-muted small p-3 rounded border bg-light">
                <i class="bi bi-info-circle me-1"></i>Save the room first, then add images from the edit page.
            </div>
        @endif
    </div>
</div>
