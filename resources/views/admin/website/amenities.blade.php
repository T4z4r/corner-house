@extends('layouts.admin.app')

@section('title', 'Website Amenities')

@section('content')
    <div class="ch-page-header">
        <div>
            <div class="ch-breadcrumb"><a href="{{ route('admin.website.index') }}">Website</a> / Amenities</div>
            <h4>Amenities</h4>
            <p class="ch-subtitle">Choose which amenities appear on the property page</p>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.website.amenities.update') }}">
        @csrf
        @method('PUT')

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Property amenities</h6>
                <small class="text-muted">Check the amenities this property offers</small>
            </div>
            <div class="card-body">
                @if ($allAmenities->isEmpty())
                    <p class="text-muted mb-0">No amenities defined yet. <a href="{{ route('admin.amenities.create') }}">Create one</a>.</p>
                @else
                    <div class="row g-3">
                        @foreach ($allAmenities as $amenity)
                            <div class="col-md-4 col-lg-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox"
                                           name="amenity_ids[]"
                                           value="{{ $amenity->id }}"
                                           id="amenity-{{ $amenity->id }}"
                                           {{ $propertyAmenityIds->contains($amenity->id) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="amenity-{{ $amenity->id }}">
                                        @if ($amenity->icon)
                                            <i class="bi bi-{{ $amenity->icon }} me-1 text-muted"></i>
                                        @endif
                                        {{ $amenity->name }}
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <button type="submit" class="btn btn-ch-primary"><i class="bi bi-check-lg me-1"></i>Save amenities</button>
    </form>
@endsection
