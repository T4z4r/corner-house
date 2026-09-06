@extends('layouts.admin.app')

@section('title', 'New Event')

@section('content')
    <div class="ch-page-header">
        <div>
            <div class="ch-breadcrumb">Management / Events / New</div>
            <h4>New Event</h4>
            <p class="ch-subtitle">Add a holiday or local event for your guests</p>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            @include('admin.events._form')
        </div>
    </div>
@endsection