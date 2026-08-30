@extends('layouts.admin.app')

@section('title', 'New Room')

@section('content')
    <div class="ch-page-header">
        <div>
            <div class="ch-breadcrumb"><a href="{{ route('admin.rooms.index', $property) }}">{{ $property->name }} / Rooms</a> / New</div>
            <h4>New Room</h4>
            <p class="ch-subtitle">{{ $property->name }}</p>
        </div>
    </div>

    <div class="card border-0 shadow-sm ch-form-card">
        <div class="ch-card-header">
            <h6><i class="bi bi-door-open"></i>Room details</h6>
            <p>{{ $property->name }}</p>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.rooms.store', $property) }}" enctype="multipart/form-data">
                @include('admin.rooms._form')
                <div class="ch-form-actions">
                    <button type="submit" class="btn btn-ch-primary"><i class="bi bi-check-lg me-1"></i>Create room</button>
                    <a href="{{ route('admin.rooms.index', $property) }}" class="btn btn-light">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
