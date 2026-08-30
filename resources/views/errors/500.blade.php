@extends('errors.layout')

@section('title', 'Server error')

@section('content')
    <div class="error-code">500</div>
    <h1 class="error-heading">Something went wrong</h1>
    <p class="error-message">An unexpected error occurred on our end. Our team has been notified. Please try again in a moment.</p>
    <div class="error-actions">
        <a href="{{ route('home') }}" class="error-btn error-btn-primary">Back to home</a>
        <a href="javascript:location.reload()" class="error-btn error-btn-outline">Try again</a>
    </div>
@endsection
