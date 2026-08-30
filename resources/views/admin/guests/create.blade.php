@extends('layouts.admin.app')

@section('title', 'New Guest')

@section('content')
    <div class="ch-page-header">
        <div>
            <div class="ch-breadcrumb"><a href="{{ route('admin.guests.index') }}">Guests</a> / New</div>
            <h4>New Guest</h4>
            <p class="ch-subtitle">Add a guest to the directory</p>
        </div>
    </div>
    <div class="card border-0 shadow-sm ch-form-card">
        <div class="ch-card-header">
            <h6><i class="bi bi-person-plus"></i>Guest details</h6>
            <p>Contact and preference information for this guest</p>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.guests.store') }}">
                @include('admin.guests._form')
                <div class="ch-form-actions">
                    <button class="btn btn-ch-primary"><i class="bi bi-check-lg me-1"></i>Create guest</button>
                    <a href="{{ route('admin.guests.index') }}" class="btn btn-light">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
