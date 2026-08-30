@extends('layouts.admin.app')

@section('title', 'Platform Links')

@section('content')
    <div class="ch-page-header">
        <div>
            <div class="ch-breadcrumb"><a href="{{ route('admin.website.index') }}">Website</a> / Platform Links</div>
            <h4>Platform Links</h4>
            <p class="ch-subtitle">Links shown on the "Book direct" section of the property page</p>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.website.platforms.update') }}">
        @csrf
        @method('PUT')

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white"><h6 class="mb-0">Listing URLs</h6></div>
            <div class="card-body">
                <p class="text-muted small mb-3">Paste the full URL to your listing on each platform. These links appear on the property page so guests can compare or book through their preferred platform.</p>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold"><i class="bi bi-house-heart me-1"></i>Airbnb</label>
                        <input type="url" name="platform_airbnb_url" class="form-control"
                               value="{{ $settings->firstWhere('key', 'platform_airbnb_url')->value ?? '' }}"
                               placeholder="https://www.airbnb.com/rooms/...">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold"><i class="bi bi-building me-1"></i>Booking.com</label>
                        <input type="url" name="platform_booking_url" class="form-control"
                               value="{{ $settings->firstWhere('key', 'platform_booking_url')->value ?? '' }}"
                               placeholder="https://www.booking.com/hotel/...">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold"><i class="bi bi-house-door me-1"></i>VRBO</label>
                        <input type="url" name="platform_vrbo_url" class="form-control"
                               value="{{ $settings->firstWhere('key', 'platform_vrbo_url')->value ?? '' }}"
                               placeholder="https://www.vrbo.com/...">
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white"><h6 class="mb-0">Direct Booking</h6></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Discount vs platforms</label>
                        <div class="input-group">
                            <input type="number" class="form-control" value="{{ $settings->firstWhere('key', 'direct_booking_discount')->value ?? '10' }}" disabled>
                            <span class="input-group-text">%</span>
                        </div>
                        <small class="text-muted">Configured in booking settings</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Damage deposit</label>
                        <div class="input-group">
                            <span class="input-group-text">£</span>
                            <input type="number" class="form-control" value="{{ $settings->firstWhere('key', 'damage_deposit')->value ?? '950' }}" disabled>
                        </div>
                        <small class="text-muted">Configured in booking settings</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Cleaning fee</label>
                        <div class="input-group">
                            <span class="input-group-text">£</span>
                            <input type="number" class="form-control" value="{{ $settings->firstWhere('key', 'cleaning_fee')->value ?? '50' }}" disabled>
                        </div>
                        <small class="text-muted">Configured in booking settings</small>
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-ch-primary"><i class="bi bi-check-lg me-1"></i>Save platform links</button>
    </form>
@endsection
