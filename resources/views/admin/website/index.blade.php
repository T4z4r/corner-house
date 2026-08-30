@extends('layouts.admin.app')

@section('title', 'Website Management')

@section('content')
    <div class="ch-page-header">
        <div>
            <div class="ch-breadcrumb">Website / Overview</div>
            <h4>Website Management</h4>
            <p class="ch-subtitle">Manage everything visitors see on the public website</p>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-6 col-lg-4">
            <a href="{{ route('admin.website.house-rules') }}" class="card border-0 shadow-sm text-decoration-none h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center" style="width:48px;height:48px;">
                            <i class="bi bi-list-check fs-5 text-success"></i>
                        </div>
                        <h6 class="mb-0">House Rules</h6>
                    </div>
                    <p class="text-muted small mb-0">Check-in/out times, pets, smoking, children, parties, and custom rules.</p>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-lg-4">
            <a href="{{ route('admin.website.content') }}" class="card border-0 shadow-sm text-decoration-none h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center" style="width:48px;height:48px;">
                            <i class="bi bi-pencil-square fs-5 text-success"></i>
                        </div>
                        <h6 class="mb-0">Property Content</h6>
                    </div>
                    <p class="text-muted small mb-0">Name, description, address, capacity, bedrooms, bathrooms.</p>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-lg-4">
            <a href="{{ route('admin.website.amenities') }}" class="card border-0 shadow-sm text-decoration-none h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center" style="width:48px;height:48px;">
                            <i class="bi bi-plus-circle fs-5 text-success"></i>
                        </div>
                        <h6 class="mb-0">Amenities</h6>
                    </div>
                    <p class="text-muted small mb-0">Toggle which amenities appear on the property page.</p>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-lg-4">
            <a href="{{ route('admin.gallery.index') }}" class="card border-0 shadow-sm text-decoration-none h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center" style="width:48px;height:48px;">
                            <i class="bi bi-images fs-5 text-success"></i>
                        </div>
                        <h6 class="mb-0">Gallery</h6>
                    </div>
                    <p class="text-muted small mb-0">Upload, reorder, and manage gallery images.</p>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-lg-4">
            <a href="{{ route('admin.website.platforms') }}" class="card border-0 shadow-sm text-decoration-none h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center" style="width:48px;height:48px;">
                            <i class="bi bi-globe fs-5 text-success"></i>
                        </div>
                        <h6 class="mb-0">Platform Links</h6>
                    </div>
                    <p class="text-muted small mb-0">Airbnb, Booking.com, VRBO listing URLs for direct booking page.</p>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-lg-4">
            <a href="{{ route('admin.settings.website') }}" class="card border-0 shadow-sm text-decoration-none h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center" style="width:48px;height:48px;">
                            <i class="bi bi-gear fs-5 text-success"></i>
                        </div>
                        <h6 class="mb-0">Website Settings</h6>
                    </div>
                    <p class="text-muted small mb-0">Hero, tagline, contact info, social links, footer, logo.</p>
                </div>
            </a>
        </div>
    </div>
@endsection
