@extends('layouts.admin.app')

@section('title', 'New Property')

@section('content')
    <div class="ch-page-header">
        <div>
            <div class="ch-breadcrumb"><a href="{{ route('admin.properties.index') }}">Properties</a> / New</div>
            <h4>New Property</h4>
            <p class="ch-subtitle">Add a property to the platform</p>
        </div>
    </div>
    <div class="card border-0 shadow-sm ch-form-card">
        <div class="ch-card-header">
            <h6><i class="bi bi-building-add"></i>Property details</h6>
            <p>Basic information, address and configuration</p>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.properties.store') }}">
                @include('admin.properties._form')
                <div class="ch-form-actions">
                    <button class="btn btn-ch-primary"><i class="bi bi-check-lg me-1"></i>Create property</button>
                    <a href="{{ route('admin.properties.index') }}" class="btn btn-light">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
