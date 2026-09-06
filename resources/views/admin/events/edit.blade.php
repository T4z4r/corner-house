@extends('layouts.admin.app')

@section('title', 'Edit Event')

@section('content')
    <div class="ch-page-header">
        <div>
            <div class="ch-breadcrumb">Management / Events / Edit</div>
            <h4>Edit Event</h4>
            <p class="ch-subtitle">{{ $item->title }}</p>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            @include('admin.events._form', ['item' => $item])
        </div>
    </div>
@endsection