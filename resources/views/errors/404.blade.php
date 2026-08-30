@extends('errors.layout')

@section('title', 'Page not found')

@section('content')
    <div class="error-code">404</div>
    <h1 class="error-heading">Page not found</h1>
    <p class="error-message">The page you're looking for doesn't exist or has been moved. Let's get you back on track.</p>
    <div class="error-actions">
        <a href="{{ route('home') }}" class="error-btn error-btn-primary">Back to home</a>
        <a href="{{ route('booking.search') }}" class="error-btn error-btn-outline">Check availability</a>
    </div>
@endsection
