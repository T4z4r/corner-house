@extends('layouts.auth.login')

@section('title', 'Sign in')

@section('content')
    <div class="ch-auth-card">
        <div class="ch-auth-head">
            <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                <path d="M7.293 1.5a1 1 0 0 1 1.414 0L11 3.793V2.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v3.293l2.354 2.353a.5.5 0 0 1-.708.707L8 2.207 1.354 8.853a.5.5 0 1 1-.708-.707z"/>
                <path d="m14 9.293-6-6-6 6V13.5A1.5 1.5 0 0 0 3.5 15h9a1.5 1.5 0 0 0 1.5-1.5zm-6-.811c1.664-1.673 5.825 1.254 0 5.018-5.825-3.764-1.664-6.691 0-5.018"/>
            </svg>
            <h1>Welcome back</h1>
            <p>Sign in to the {{ config('app.name') }} platform</p>
        </div>

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="ch-auth-field">
                <label for="email" class="ch-auth-label">Email address</label>
                <input type="email"
                       class="ch-auth-input @error('email') is-invalid @enderror"
                       id="email"
                       name="email"
                       value="{{ old('email') }}"
                       placeholder="you@example.com"
                       required
                       autofocus>
                @error('email')
                    <div class="ch-auth-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="ch-auth-field">
                <label for="password" class="ch-auth-label">Password</label>
                <div class="ch-auth-wrap">
                    <input type="password"
                           class="ch-auth-input @error('password') is-invalid @enderror"
                           id="password"
                           name="password"
                           placeholder="Enter your password"
                           required>
                    <button type="button" class="ch-auth-toggle" id="togglePassword" aria-label="Show password" tabindex="-1">
                        <svg id="iconEye" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                        <svg id="iconEyeSlash" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" hidden>
                            <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/>
                            <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/>
                            <path d="M6.61 6.61A13.53 13.53 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/>
                            <line x1="2" y1="2" x2="22" y2="22"/>
                        </svg>
                    </button>
                </div>
                @error('password')
                    <div class="ch-auth-error">{{ $message }}</div>
                @enderror
            </div>

            <label class="ch-auth-check">
                <input type="checkbox" name="remember" id="remember">
                <span>Remember me</span>
            </label>

            <button type="submit" class="ch-auth-btn">Sign in</button>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            var toggle = document.getElementById('togglePassword');
            var input = document.getElementById('password');
            var eye = document.getElementById('iconEye');
            var slash = document.getElementById('iconEyeSlash');
            if (!toggle || !input) return;

            toggle.addEventListener('click', function () {
                var show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                eye.hidden = show;
                slash.hidden = !show;
                toggle.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            });
        })();
    </script>
@endpush