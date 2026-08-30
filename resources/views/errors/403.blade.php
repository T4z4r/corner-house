@extends('errors.layout')

@section('title', 'Access denied')

@section('content')
    <div class="error-code">403</div>
    <h1 class="error-heading">Access denied</h1>
    <p class="error-message">You don't have permission to view this page. If you believe this is a mistake, please get in touch.</p>
    <div class="error-actions">
        <a href="{{ route('home') }}" class="error-btn error-btn-primary">Back to home</a>
        <a href="{{ route('contact') }}" class="error-btn error-btn-outline">Contact us</a>
    </div>
@endsection
