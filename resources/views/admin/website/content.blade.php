@extends('layouts.admin.app')

@section('title', 'Property Content')

@section('content')
    <div class="ch-page-header">
        <div>
            <div class="ch-breadcrumb"><a href="{{ route('admin.website.index') }}">Website</a> / Property Content</div>
            <h4>Property Content</h4>
            <p class="ch-subtitle">What visitors see on the property and booking pages</p>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.website.content.update') }}">
        @csrf
        @method('PUT')

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white"><h6 class="mb-0">Basic Information</h6></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Property name</label>
                            <input type="text" name="name" class="form-control" value="{{ $property->name ?? '' }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Short description</label>
                            <input type="text" name="short_description" class="form-control" value="{{ $property->short_description ?? '' }}" maxlength="500" placeholder="One line summary shown on cards and search results">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Full description</label>
                            <textarea name="description" class="form-control" rows="5" placeholder="Detailed description shown on the property page">{{ $property->description ?? '' }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white"><h6 class="mb-0">Location</h6></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-semibold">Address line 1</label>
                                <input type="text" name="address_line_1" class="form-control" value="{{ $property->address_line_1 ?? '' }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Address line 2</label>
                                <input type="text" name="address_line_2" class="form-control" value="{{ $property->address_line_2 ?? '' }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">City</label>
                                <input type="text" name="city" class="form-control" value="{{ $property->city ?? '' }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Postcode</label>
                                <input type="text" name="postcode" class="form-control" value="{{ $property->postcode ?? '' }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Country</label>
                                <input type="text" name="country" class="form-control" value="{{ $property->country ?? '' }}" maxlength="2" placeholder="GB">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Latitude</label>
                                <input type="number" step="any" name="latitude" class="form-control" value="{{ $property->latitude ?? '' }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Longitude</label>
                                <input type="number" step="any" name="longitude" class="form-control" value="{{ $property->longitude ?? '' }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white"><h6 class="mb-0">Capacity</h6></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Max guests</label>
                            <input type="number" name="capacity" class="form-control" value="{{ $property->capacity ?? '' }}" min="1">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Bedrooms</label>
                            <input type="number" name="bedrooms" class="form-control" value="{{ $property->bedrooms ?? '' }}" min="0">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Bathrooms</label>
                            <input type="number" name="bathrooms" class="form-control" value="{{ $property->bathrooms ?? '' }}" min="0">
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white"><h6 class="mb-0">Quick stats</h6></div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Rooms</span>
                            <span class="fw-semibold">{{ $property->rooms->count() ?? 0 }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Amenities</span>
                            <span class="fw-semibold">{{ $property->amenities->count() ?? 0 }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Images</span>
                            <span class="fw-semibold">{{ $property->images->count() ?? 0 }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4">
            <button type="submit" class="btn btn-ch-primary"><i class="bi bi-check-lg me-1"></i>Save content</button>
        </div>
    </form>
@endsection
