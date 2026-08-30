@extends('layouts.admin.app')
@section('title', 'Channels')
@section('content')
<div class="ch-page-header">
    <div>
        <div class="ch-breadcrumb">Channels</div>
        <h4>Beds24 integration</h4>
        <p class="ch-subtitle mb-0">
            API v2 ·
            <a href="{{ $swaggerUrl }}" target="_blank" rel="noopener">Swagger docs &amp; test UI</a>
        </p>
    </div>
    @can('channels.sync')
        <form method="POST" action="{{ route('admin.channels.sync') }}">@csrf<button class="btn btn-ch-primary">Sync from Beds24</button></form>
    @endcan
</div>
<div class="row g-3">
    <div class="col-12" id="beds24-setup">
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
                        Use the invite code from Beds24 to exchange for a refresh token and short-lived access token.
                    </p>
                @else
                    <p class="text-muted mb-0">You need channel configure permission to set up Beds24 credentials.</p>
                @endcan
            </div>
        </div>
    </div>
    <div class="col-lg-6" id="beds24-integrations">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white">Accounts</div>
            <div class="card-body">
                @can('channels.configure')
                    <form method="POST" action="{{ route('admin.channels.store') }}" class="row g-2 mb-4">
                        @csrf
                        <div class="col-md-4"><input name="name" class="form-control" placeholder="Name" required></div>
                        <div class="col-md-4">
                            <select name="provider" class="form-select"><option value="beds24">Beds24</option></select>
                        </div>
                        <div class="col-md-4">
                            <select name="status" class="form-select"><option value="inactive">Inactive</option><option value="active">Active</option></select>
                        </div>
                        <div class="col-12"><input name="refresh_token" class="form-control" placeholder="Refresh token (optional if using invite code)"></div>
                        <div class="col-12"><button class="btn btn-ch-primary btn-sm">Save account</button></div>
                    </form>
                @endcan
                @foreach ($accounts as $account)
                    <div class="border-bottom py-3">
                        <div class="d-flex justify-content-between">
                            <div>
                                <strong>{{ $account->name }}</strong>
                                <div class="small text-muted">{{ $account->provider }} · {{ $account->status }} · {{ $account->mappings_count }} mappings</div>
                            </div>
                            <span class="small">{{ $account->last_synced_at?->diffForHumans() ?? 'Never synced' }}</span>
                        </div>
                        @can('channels.configure')
                            @if ($account->provider === 'beds24')
                                <form method="POST" action="{{ route('admin.channels.setup', $account) }}" class="row g-2 mt-2">
                                    @csrf
                                    <div class="col-8"><input name="invite_code" class="form-control form-control-sm" placeholder="Beds24 invite code" required></div>
                                    <div class="col-4"><button class="btn btn-sm btn-outline-primary w-100">Exchange code</button></div>
                                </form>
                                <button type="button" class="btn btn-sm btn-outline-secondary mt-2" data-inspect-token="{{ $account->id }}">Inspect token</button>
                                @if (! empty($account->settings['scopes']))
                                    <div class="small text-muted mt-1">Scopes: {{ implode(', ', $account->settings['scopes']) }}</div>
                                @endif
                                <div class="d-flex justify-content-end mt-2">
                                    <button type="button" class="btn btn-sm btn-outline-secondary d-none" data-copy-target="tokenDetails-{{ $account->id }}">Copy</button>
                                </div>
                                <pre class="small bg-light p-2 rounded mt-1 d-none" id="tokenDetails-{{ $account->id }}"></pre>
                            @endif
                        @endcan
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white">Room mappings</div>
            <div class="card-body">
                @can('channels.configure')
                    <form method="POST" action="{{ route('admin.channels.import') }}" class="row g-2 mb-3">
                        @csrf
                        <div class="col-md-5">
                            <select name="channel_account_id" class="form-select" required>
                                @foreach ($accounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-5">
                            <select name="property_id" class="form-select" required>
                                @foreach ($properties as $property)
                                    <option value="{{ $property->id }}">{{ $property->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2"><button class="btn btn-outline-primary w-100">Import</button></div>
                    </form>
                    <form method="POST" action="{{ route('admin.channels.mappings.store') }}" class="row g-2 mb-3">
                        @csrf
                        <div class="col-md-6">
                            <select name="channel_account_id" class="form-select" required>
                                @foreach ($accounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <select name="property_id" class="form-select" required>
                                @foreach ($properties as $property)
                                    <option value="{{ $property->id }}">{{ $property->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <select name="room_id" class="form-select">
                                <option value="">Corner House room</option>
                                @foreach ($rooms as $room)
                                    <option value="{{ $room->id }}">{{ $room->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6"><input name="external_room_id" class="form-control" placeholder="Beds24 room ID"></div>
                        <div class="col-12"><button class="btn btn-ch-primary btn-sm">Save mapping</button></div>
                    </form>
                @endcan
                @foreach ($mappings as $mapping)
                    <div class="small border-bottom py-2">
                        {{ $mapping->room?->name ?? 'Unmapped' }}
                        → {{ $mapping->provider }} room {{ $mapping->external_room_id }}
                        @if ($mapping->metadata['beds24_room_name'] ?? null)
                            ({{ $mapping->metadata['beds24_room_name'] }})
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    <div class="col-12" id="beds24-test-window">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span>API test window</span>
                <a class="small" href="{{ $swaggerUrl }}" target="_blank" rel="noopener">Open official Swagger</a>
            </div>
            <div class="card-body">
                @can('channels.configure')
                    <form id="beds24TestForm" class="row g-2">
                        @csrf
                        <div class="col-md-3">
                            <select name="account_id" id="beds24Account" class="form-select" required>
                                @foreach ($accounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="method" id="beds24Method" class="form-select">
                                <option>GET</option>
                                <option>POST</option>
                                <option>PATCH</option>
                                <option>DELETE</option>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <select name="endpoint" id="beds24Endpoint" class="form-select">
                                @foreach ($testEndpoints as $endpoint)
                                    <option value="{{ $endpoint }}">{{ $endpoint }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-ch-primary w-100" type="submit">Run</button>
                        </div>
                        <div class="col-12">
                            <textarea id="beds24Body" class="form-control font-monospace" rows="4" placeholder='JSON body/query. For auth endpoints use {"code":"..."} or {"refreshToken":"..."}'></textarea>
                        </div>
                    </form>
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <span class="small text-muted">Response</span>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="beds24Copy" data-copy-target="beds24Result">Copy</button>
                    </div>
                    <pre id="beds24Result" class="bg-dark text-white p-3 rounded mt-2 small mb-0" style="min-height: 140px;">Response will appear here.</pre>
                @else
                    <p class="text-muted mb-0">You need channel configure permission to use the test window.</p>
                @endcan
            </div>
        </div>
    </div>
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">Recent sync logs</div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>Channel</th><th>Operation</th><th>Status</th><th>When</th></tr></thead>
                    <tbody>
                        @forelse ($logs as $log)
                            <tr>
                                <td>{{ $log->channel }}</td>
                                <td>{{ $log->operation }}</td>
                                <td>{{ $log->status }}</td>
                                <td>{{ $log->created_at->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-muted">No sync activity yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const copyText = async (button) => {
        const targetId = button.getAttribute('data-copy-target');
        const target = document.getElementById(targetId);
        if (!target || !target.textContent.trim()) {
            return;
        }
        try {
            await navigator.clipboard.writeText(target.textContent);
            const original = button.textContent;
            button.textContent = 'Copied';
            setTimeout(() => { button.textContent = original; }, 1500);
        } catch (e) {
            button.textContent = 'Copy failed';
        }
    };

    document.querySelectorAll('[data-copy-target]').forEach((button) => {
        button.addEventListener('click', () => copyText(button));
    });

    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const jsonHeaders = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrf,
    };

    document.querySelectorAll('[data-inspect-token]').forEach((button) => {
        button.addEventListener('click', async function () {
            const id = button.getAttribute('data-inspect-token');
            const output = document.getElementById('tokenDetails-' + id);
            if (output) {
                output.classList.remove('d-none');
                output.textContent = 'Checking token…';
            }
            try {
                const response = await fetch('/admin/channels/' + id + '/details', {
                    method: 'POST',
                    headers: jsonHeaders,
                    body: '{}',
                });
                const data = await response.json();
                if (output) {
                    output.textContent = JSON.stringify(data, null, 2);
                    const copyBtn = document.querySelector('[data-copy-target="tokenDetails-' + id + '"]');
                    if (copyBtn) {
                        copyBtn.classList.remove('d-none');
                    }
                }
            } catch (e) {
                if (output) {
                    output.textContent = 'Request failed.';
                }
            }
        });
    });

    const form = document.getElementById('beds24TestForm');
    if (!form) return;
    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        const accountId = document.getElementById('beds24Account').value;
        const result = document.getElementById('beds24Result');
        result.textContent = 'Running…';
        try {
            const response = await fetch('/admin/channels/' + accountId + '/test', {
                method: 'POST',
                headers: jsonHeaders,
                body: JSON.stringify({
                    method: document.getElementById('beds24Method').value,
                    endpoint: document.getElementById('beds24Endpoint').value,
                    body: document.getElementById('beds24Body').value,
                }),
            });
            const data = await response.json();
            result.textContent = JSON.stringify(data, null, 2);
        } catch (e) {
            result.textContent = 'Request failed.';
        }
    });
});
</script>
@endpush
