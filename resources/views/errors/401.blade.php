@extends('errors.layout')

@section('title', 'Unauthorised')

@section('content')
    <div class="error-code">401</div>
    <h1 class="error-heading">Unauthorised</h1>
    <p class="error-message">You need to sign in to access this page. Please log in with your credentials.</p>
    <div class="error-actions">
        <a href="{{ route('login') }}" class="error-btn error-btn-primary">Sign in</a>
        <a href="{{ route('home') }}" class="error-btn error-btn-outline">Back to home</a>
    </div>
@endsection
