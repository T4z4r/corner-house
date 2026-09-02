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
                            <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                            @error('name') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
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
                            <input type="password" name="current_password" class="form-control" required>
                            @error('current_password') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New password</label>
                            <input type="password" name="password" class="form-control" required>
                            @error('password') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm new password</label>
                            <input type="password" name="password_confirmation" class="form-control" required>
                        </div>
                        <div class="form-text mb-3">Password must be at least 8 characters.</div>
                        <button class="btn btn-ch-primary">Update password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection