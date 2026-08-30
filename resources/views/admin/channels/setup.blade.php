@extends('layouts.admin.app')
@section('title', 'Channels')
@section('content')
<div class="ch-page-header">
    <div>
        <div class="ch-breadcrumb">Channels</div>
        <h4>Beds24 setup</h4>
        <p class="ch-subtitle mb-0">
            Connect a Beds24 account with an invite code, then inspect the token status from the integrations page.
        </p>
    </div>
    <a href="{{ route('admin.channels.integrations') }}" class="btn btn-outline-primary">Beds24 integrations</a>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">Beds24 credentials setup</div>
            <div class="card-body">
                @can('channels.configure')
                    <form method="POST" action="{{ route('admin.channels.credentials.setup') }}" class="row g-2">
                        @csrf
                        <div class="col-md-4">
                            <select name="account_id" class="form-select" required>
                                @forelse ($accounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->name }}</option>
                                @empty
                                    <option value="">No Beds24 accounts yet</option>
                                @endforelse
                            </select>
                        </div>
                        <div class="col-md-6">
                            <input name="invite_code" class="form-control" placeholder="Beds24 invite code" required>
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-ch-primary w-100" type="submit">Exchange code</button>
                        </div>
                    </form>
                    <p class="small text-muted mb-0 mt-2">
                        The invite code is exchanged for a refresh token and short-lived access token.
                    </p>
                @else
                    <p class="text-muted mb-0">You need channel configure permission to set up Beds24 credentials.</p>
                @endcan
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white">Configured accounts</div>
            <div class="card-body">
                @forelse ($accounts as $account)
                    <div class="border-bottom py-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <strong>{{ $account->name }}</strong>
                                <div class="small text-muted">{{ $account->status }}</div>
                            </div>
                            <a href="{{ route('admin.channels.integrations') }}" class="small">Inspect</a>
                        </div>
                        @if (! empty($account->settings['scopes']))
                            <div class="small text-muted mt-1">Scopes: {{ implode(', ', $account->settings['scopes']) }}</div>
                        @else
                            <div class="small text-muted mt-1">No token details yet.</div>
                        @endif
                    </div>
                @empty
                    <p class="text-muted mb-0">No Beds24 accounts configured yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
