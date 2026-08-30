@extends('layouts.auth.app')

@section('title', 'Register')

@section('content')
    <div class="card shadow-lg border-0 rounded-4">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
                <i class="bi bi-house-heart-fill display-4 ch-brand" style="color: var(--ch-primary);"></i>
                <h4 class="mt-2 mb-1 fw-bold">Create an account</h4>
                <p class="text-muted small mb-0">Register to access the {{ config('app.name') }} platform</p>
            </div>

            <form method="POST" action="{{ route('register') }}">
                @csrf
                <div class="mb-3">
                    <label for="name" class="form-label">Full name</label>
                    <input type="text"
                           class="form-control @error('name') is-invalid @enderror"
                           id="name"
                           name="name"
                           value="{{ old('name') }}"
                           required
                           autofocus>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email address</label>
                    <input type="email"
                           class="form-control @error('email') is-invalid @enderror"
                           id="email"
                           name="email"
                           value="{{ old('email') }}"
                           required>
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password"
                           class="form-control @error('password') is-invalid @enderror"
                           id="password"
                           name="password"
                           required>
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="password_confirmation" class="form-label">Confirm password</label>
                    <input type="password"
                           class="form-control"
                           id="password_confirmation"
                           name="password_confirmation"
                           required>
                </div>

                <button type="submit" class="btn btn-ch-primary w-100 py-2 fw-semibold">Register</button>
            </form>

            <p class="text-center text-muted small mt-4 mb-0">
                Already have an account?
                <a href="{{ route('login') }}" class="text-decoration-none">Sign in</a>
            </p>
        </div>
    </div>
@endsection
