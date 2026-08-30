@extends('layouts.admin.app')

@section('title', 'Edit Guest')

@section('content')
    <div class="ch-page-header">
        <div>
            <div class="ch-breadcrumb"><a href="{{ route('admin.guests.index') }}">Guests</a> / <a href="{{ route('admin.guests.show', $guest) }}">Profile</a> / Edit</div>
            <h4>Edit Guest</h4>
            <p class="ch-subtitle">{{ $guest->full_name }}</p>
        </div>
    </div>
    <div class="card border-0 shadow-sm ch-form-card">
        <div class="ch-card-header">
            <h6><i class="bi bi-person-gear"></i>Edit guest</h6>
            <p>{{ $guest->full_name }}</p>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.guests.update', $guest) }}">
                @method('PUT')
                @include('admin.guests._form')
                <div class="ch-form-actions">
                    <button class="btn btn-ch-primary"><i class="bi bi-check-lg me-1"></i>Save changes</button>
                </div>
            </form>
        </div>
    </div>
@endsection
