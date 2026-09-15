@extends('layouts.admin.app')

@section('title', 'My account')

@section('content')
    <div class="ch-page-header">
        <div>
            <div class="ch-breadcrumb">Account</div>
            <h4>My account</h4>
            <p class="ch-subtitle">Update your profile details and password</p>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title d-flex align-items-center gap-2 mb-3">
                        <i class="bi bi-person-badge"></i> Profile details
                    </h5>
                    <form method="POST" action="{{ route('account.profile.update') }}">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" placeholder="Your name" value="{{ old('name', $user->name) }}" required>
                            @error('name') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" placeholder="you@example.com" value="{{ old('email', $user->email) }}" required>
                            @error('email') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                        <button class="btn btn-ch-primary">Save details</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title d-flex align-items-center gap-2 mb-3">
                        <i class="bi bi-shield-lock"></i> Change password
                    </h5>
                    <form method="POST" action="{{ route('account.password.update') }}">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label class="form-label">Current password</label>
                            <div class="input-group">
                                <input type="password" name="current_password" class="form-control" placeholder="Enter current password" required>
                                <button type="button" class="btn btn-outline-secondary ch-pw-toggle" data-target="current_password" aria-label="Show password" tabindex="-1">
                                    <svg class="ch-pw-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                    <svg class="ch-pw-eye-slash" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.53 13.53 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" y1="2" x2="22" y2="22"/></svg>
                                </button>
                            </div>
                            @error('current_password') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New password</label>
                            <div class="input-group">
                                <input type="password" name="password" class="form-control" placeholder="Enter new password" required>
                                <button type="button" class="btn btn-outline-secondary ch-pw-toggle" data-target="password" aria-label="Show password" tabindex="-1">
                                    <svg class="ch-pw-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                    <svg class="ch-pw-eye-slash" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.53 13.53 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" y1="2" x2="22" y2="22"/></svg>
                                </button>
                            </div>
                            @error('password') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm new password</label>
                            <div class="input-group">
                                <input type="password" name="password_confirmation" class="form-control" placeholder="Re-enter new password" required>
                                <button type="button" class="btn btn-outline-secondary ch-pw-toggle" data-target="password_confirmation" aria-label="Show password" tabindex="-1">
                                    <svg class="ch-pw-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                    <svg class="ch-pw-eye-slash" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.53 13.53 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" y1="2" x2="22" y2="22"/></svg>
                                </button>
                            </div>
                        </div>
                        <div class="form-text mb-3">Password must be at least 8 characters and include uppercase, lowercase, numbers, and symbols.</div>
                        <button class="btn btn-ch-primary">Update password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('.ch-pw-toggle').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var input = document.querySelector('input[name="' + btn.dataset.target + '"]');
                if (!input) return;
                var show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                btn.querySelector('.ch-pw-eye').style.display = show ? 'none' : '';
                btn.querySelector('.ch-pw-eye-slash').style.display = show ? '' : 'none';
                btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            });
        });
    </script>
@endpush