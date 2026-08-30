@extends('layouts.admin.app')

@section('title', 'Edit channel account')

@section('content')
    <div class="ch-page-header">
        <div>
            <div class="ch-breadcrumb">Channels / Edit</div>
            <h4>Edit channel account</h4>
            <p class="ch-subtitle">{{ $account->name }}</p>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">Account details</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.channels.update', $account) }}">
                        @csrf
                        @method('PUT')
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Name</label>
                                <input type="text" name="name" class="form-control" value="{{ old('name', $account->name) }}" required>
                                @error('name') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Provider</label>
                                <input type="text" class="form-control" value="{{ ucfirst($account->provider) }}" disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select" required>
                                    <option value="active" @selected($account->status === 'active')>Active</option>
                                    <option value="inactive" @selected($account->status === 'inactive')>Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Mappings</label>
                                <input type="text" class="form-control" value="{{ $account->mappings_count ?? $account->mappings()->count() }}" disabled>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Refresh token <small class="text-muted">(leave blank to keep current)</small></label>
                                <input type="password" name="refresh_token" class="form-control" value="" autocomplete="new-password" placeholder="{{ $account->credentials['refresh_token'] ?? 'Not set' }}">
                                @if (! empty($account->credentials['refresh_token']))
                                    <div class="form-text">A token is stored. Enter a new value only if you want to replace it.</div>
                                @endif
                            </div>
                        </div>
                        <div class="mt-4 d-flex gap-2">
                            <button type="submit" class="btn btn-ch-primary">Save changes</button>
                            <a href="{{ route('admin.channels.integrations') }}" class="btn btn-light">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">Danger zone</div>
                <div class="card-body">
                    <p class="text-muted mb-3">Deleting this account will remove all associated mappings and sync logs. This action cannot be undone.</p>
                    <form method="POST" action="{{ route('admin.channels.destroy', $account) }}" onsubmit="return confirm('Are you sure you want to delete this channel account? This cannot be undone.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash me-1"></i>Delete account</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
