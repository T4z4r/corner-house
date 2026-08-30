@extends('errors.layout')

@section('title', 'Page expired')

@section('content')
    <div class="error-code">419</div>
    <h1 class="error-heading">Page expired</h1>
    <p class="error-message">This page has expired, usually because you submitted a form without a valid security token. Please try again.</p>
    <div class="error-actions">
        <a href="{{ route('home') }}" class="error-btn error-btn-primary">Back to home</a>
        <a href="javascript:history.back()" class="error-btn error-btn-outline">Go back</a>
    </div>
@endsection
