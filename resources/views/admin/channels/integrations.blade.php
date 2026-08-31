@extends('layouts.admin.app')
@section('title', 'Channels')
@section('content')
<div class="ch-page-header">
    <div>
        <div class="ch-breadcrumb">Channels</div>
        <h4>Beds24 integrations</h4>
        <p class="ch-subtitle mb-0">
            API v2 ·
            <a href="{{ $swaggerUrl }}" target="_blank" rel="noopener">Swagger docs &amp; test UI</a>
        </p>
    </div>
    <div class="d-flex gap-2">
        @can('channels.configure')
            <a href="{{ route('admin.channels.setup.page') }}" class="btn btn-outline-primary">Beds24 setup</a>
        @endcan
        @can('channels.sync')
            <form method="POST" action="{{ route('admin.channels.sync') }}">@csrf<button class="btn btn-ch-primary">Sync from Beds24</button></form>
        @endcan
    </div>
</div>
<div class="row g-3">
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
                            <div class="d-flex align-items-center gap-2">
                                <span class="small">{{ $account->last_synced_at?->diffForHumans() ?? 'Never synced' }}</span>
                                @can('channels.configure')
                                    <a href="{{ route('admin.channels.edit', $account) }}" class="btn btn-sm btn-outline-primary" title="Edit account"><i class="bi bi-pencil"></i></a>
                                    <form method="POST" action="{{ route('admin.channels.destroy', $account) }}" class="d-inline" onsubmit="return confirm('Delete this account and all its mappings?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete account"><i class="bi bi-trash"></i></button>
                                    </form>
                                @endcan
                            </div>
                        </div>
                        @can('channels.configure')
                            @if ($account->provider === 'beds24')
                                <form method="POST" action="{{ route('admin.channels.setup', $account) }}" class="row g-2 mt-2">
                                    @csrf
                                    <div class="col-8"><input name="invite_code" class="form-control form-control-sm" placeholder="Beds24 invite code" required></div>
                                    <div class="col-4"><button class="btn btn-sm btn-outline-primary w-100">Exchange code</button></div>
                                </form>
                                <button type="button" class="btn btn-sm btn-outline-secondary mt-2" data-inspect-token="{{ $account->id }}">Inspect token</button>
                                <form method="POST" action="{{ route('admin.channels.properties.sync') }}" class="mt-2">
                                    @csrf
                                    <input type="hidden" name="channel_account_id" value="{{ $account->id }}">
                                    <button type="submit" class="btn btn-sm btn-outline-success">Save properties to system</button>
                                </form>
                                <form method="POST" action="{{ route('admin.channels.rooms.sync') }}" class="mt-2">
                                    @csrf
                                    <input type="hidden" name="channel_account_id" value="{{ $account->id }}">
                                    <button type="submit" class="btn btn-sm btn-outline-success">Save rooms to system</button>
                                </form>
                                @if (! empty($account->settings['scopes']))
                                    <div class="small text-muted mt-1">Scopes: {{ implode(', ', $account->settings['scopes']) }}</div>
                                @endif
                                <pre class="small bg-light p-2 rounded mt-2 d-none" id="tokenDetails-{{ $account->id }}"></pre>
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
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span>Bookings publishing</span>
                <span class="small text-muted">Send bookings and guest details to Beds24</span>
            </div>
            <div class="card-body">
                @can('channels.configure')
                    @if ($reservations->isNotEmpty())
                        <div class="list-group list-group-flush bg-white rounded">
                            @foreach ($reservations as $reservation)
                                <div class="list-group-item d-flex justify-content-between align-items-center gap-3">
                                    <div>
                                        <div class="fw-semibold">{{ $reservation->reference }}</div>
                                        <div class="small text-muted">
                                            {{ $reservation->guest?->full_name ?? 'Guest' }}
                                            · {{ $reservation->room?->name ?? 'Room' }}
                                            · {{ $reservation->check_in?->format('d M Y') }} to {{ $reservation->check_out?->format('d M Y') }}
                                        </div>
                                    </div>
                                    <div class="d-flex flex-wrap justify-content-end gap-2">
                                        <form method="POST" action="{{ route('admin.channels.bookings.publish', $reservation) }}">
                                            @csrf
                                            <button class="btn btn-sm btn-ch-primary" type="submit">Publish booking</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.channels.bookings.guests.publish', $reservation) }}">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-primary" type="submit">Post guests</button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-muted small">No bookings available to publish.</div>
                    @endif
                @endcan
            </div>
        </div>
    </div>
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span>Price import</span>
                <span class="small text-muted">Fetch Beds24 calendar prices and store them locally</span>
            </div>
            <div class="card-body">
                @can('channels.configure')
                    <form method="POST" action="{{ route('admin.channels.prices.import') }}" class="row g-2 align-items-end">
                        @csrf
                        <div class="col-md-4">
                            <label class="form-label">Beds24 account</label>
                            <select name="account_id" class="form-select" required>
                                @foreach ($accounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <p class="text-muted small mb-0">This pulls Beds24 calendar prices into local pricing overrides and calendar blocks.</p>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <button class="btn btn-ch-primary" type="submit">Import prices from Beds24</button>
                        </div>
                    </form>
                @endcan
            </div>
        </div>
    </div>
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span>Property publishing</span>
                <span class="small text-muted">Send local properties and rooms to Beds24</span>
            </div>
            <div class="card-body">
                @can('channels.configure')
                    @if ($properties->isNotEmpty())
                        <div class="list-group list-group-flush bg-white rounded">
                            @foreach ($properties as $property)
                                <div class="list-group-item d-flex justify-content-between align-items-center gap-3">
                                    <div>
                                        <div class="fw-semibold">{{ $property->name }}</div>
                                        <div class="small text-muted">
                                            {{ $property->city ?? 'No city' }}
                                            · {{ $property->rooms_count }} room{{ $property->rooms_count === 1 ? '' : 's' }}
                                        </div>
                                    </div>
                                    <form method="POST" action="{{ route('admin.channels.properties.publish', $property) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-ch-primary" type="submit">Publish property</button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-muted small">No properties available to publish.</div>
                    @endif
                @endcan
            </div>
        </div>
    </div>
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span>Pricing publishing</span>
                <span class="small text-muted">Send existing local pricing changes to Beds24</span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-lg-6">
                        <div class="border rounded-3 p-3 h-100 bg-light">
                            <div class="fw-semibold mb-1">Pricing rules</div>
                            <p class="text-muted small mb-3">Choose a rule and publish its date range, adjustment and stay limits to Beds24.</p>
                            @can('channels.configure')
                                @if ($pricingRules->isNotEmpty())
                                    <div class="list-group list-group-flush bg-white rounded">
                                        @foreach ($pricingRules as $pricingRule)
                                            <div class="list-group-item d-flex justify-content-between align-items-center gap-3">
                                                <div>
                                                    <div class="fw-semibold">{{ $pricingRule->name }}</div>
                                                    <div class="small text-muted">
                                                        {{ $pricingRule->rule_type }} · priority {{ $pricingRule->priority }}
                                                        @if ($pricingRule->start_date)
                                                            · {{ $pricingRule->start_date->format('d M Y') }} to {{ $pricingRule->end_date?->format('d M Y') ?? 'open' }}
                                                        @endif
                                                    </div>
                                                </div>
                                                <form method="POST" action="{{ route('admin.channels.pricing.rules.publish', $pricingRule) }}">
                                                    @csrf
                                                    <button class="btn btn-sm btn-ch-primary" type="submit">Publish</button>
                                                </form>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="text-muted small">No pricing rules yet.</div>
                                @endif
                            @endcan
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="border rounded-3 p-3 h-100 bg-light">
                            <div class="fw-semibold mb-1">Rate overrides</div>
                            <p class="text-muted small mb-3">Choose a manual override and push the nightly rate and stay limit to Beds24.</p>
                            @can('channels.configure')
                                @if ($pricingOverrides->isNotEmpty())
                                    <div class="list-group list-group-flush bg-white rounded">
                                        @foreach ($pricingOverrides as $pricingOverride)
                                            <div class="list-group-item d-flex justify-content-between align-items-center gap-3">
                                                <div>
                                                    <div class="fw-semibold">{{ $pricingOverride->room?->name ?? 'Room' }}</div>
                                                    <div class="small text-muted">
                                                        £{{ number_format((float) $pricingOverride->rate, 2) }}
                                                        · {{ $pricingOverride->start_date->format('d M Y') }} to {{ $pricingOverride->end_date->format('d M Y') }}
                                                    </div>
                                                </div>
                                                <form method="POST" action="{{ route('admin.channels.pricing.overrides.publish', $pricingOverride) }}">
                                                    @csrf
                                                    <button class="btn btn-sm btn-ch-primary" type="submit">Publish</button>
                                                </form>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="text-muted small">No rate overrides yet.</div>
                                @endif
                            @endcan
                        </div>
                    </div>
                </div>
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
                    <pre id="beds24Result" class="bg-dark text-white p-3 rounded mt-3 small mb-0" style="min-height: 140px;">Response will appear here.</pre>
                @else
                    <p class="text-muted mb-0">You need channel configure permission to use the test window.</p>
                @endcan
            </div>
        </div>
    </div>
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">Recent sync logs</div>
            <div class="card-body pb-0">
                <form method="GET" class="row g-2 mb-3">
                    <div class="col-md-4">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" name="log_search" class="form-control" placeholder="Search channel, operation, status…" value="{{ request('log_search') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select name="log_status" class="form-select form-select-sm">
                            <option value="">All statuses</option>
                            <option value="success" @selected(request('log_status') === 'success')>Success</option>
                            <option value="failed" @selected(request('log_status') === 'failed')>Failed</option>
                            <option value="pending" @selected(request('log_status') === 'pending')>Pending</option>
                            <option value="partial" @selected(request('log_status') === 'partial')>Partial</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-sm btn-ch-primary w-100" type="submit">Filter</button>
                    </div>
                    @if (request('log_search') || request('log_status'))
                        <div class="col-md-2">
                            <a href="{{ route('admin.channels.integrations') }}" class="btn btn-sm btn-outline-secondary w-100">Clear</a>
                        </div>
                    @endif
                </form>
            </div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>Channel</th><th>Operation</th><th>Status</th><th>When</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($logs as $log)
                            <tr>
                                <td>{{ $log->channel }}</td>
                                <td>{{ $log->operation }}</td>
                                <td>{{ $log->status }}</td>
                                <td>{{ $log->created_at->diffForHumans() }}</td>
                                <td class="text-end">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-primary"
                                        data-log-details='{{ json_encode([
                                            "id" => $log->id,
                                            "channel" => $log->channel,
                                            "operation" => $log->operation,
                                            "status" => $log->status,
                                            "external_id" => $log->external_id,
                                            "error_message" => $log->error_message,
                                            "created_at" => $log->created_at?->toIso8601String(),
                                            "started_at" => $log->started_at?->toIso8601String(),
                                            "completed_at" => $log->completed_at?->toIso8601String(),
                                            "request" => $log->request,
                                            "response" => $log->response,
                                        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}'
                                    >
                                        View details
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-muted">No sync activity yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($logs->hasPages())
                <div class="card-footer bg-white">{{ $logs->links() }}</div>
            @endif
        </div>
    </div>
</div>

<div class="modal fade" id="beds24LogModal" tabindex="-1" aria-labelledby="beds24LogModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-1" id="beds24LogModalLabel">Sync log details</h5>
                    <div class="small text-muted" id="beds24LogModalSubtitle"></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <div class="small text-muted">Channel</div>
                        <div id="beds24LogChannel" class="fw-semibold"></div>
                    </div>
                    <div class="col-md-3">
                        <div class="small text-muted">Operation</div>
                        <div id="beds24LogOperation" class="fw-semibold"></div>
                    </div>
                    <div class="col-md-3">
                        <div class="small text-muted">Status</div>
                        <div id="beds24LogStatus" class="fw-semibold"></div>
                    </div>
                    <div class="col-md-3">
                        <div class="small text-muted">External ID</div>
                        <div id="beds24LogExternalId" class="fw-semibold"></div>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="small text-muted mb-1">Error message</div>
                    <div id="beds24LogErrorMessage" class="alert alert-warning mb-0 d-none"></div>
                </div>

                <div class="row g-3">
                    <div class="col-lg-6">
                        <div class="card h-100">
                            <div class="card-header bg-white">Request</div>
                            <div class="card-body">
                                <pre id="beds24LogRequest" class="bg-light border rounded p-3 small mb-0" style="min-height: 240px;"></pre>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card h-100">
                            <div class="card-header bg-white">Response</div>
                            <div class="card-body">
                                <pre id="beds24LogResponse" class="bg-light border rounded p-3 small mb-0" style="min-height: 240px;"></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const logModalElement = document.getElementById('beds24LogModal');
    const logModal = logModalElement ? new bootstrap.Modal(logModalElement) : null;
    const jsonHeaders = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrf,
    };

    const logSubtitle = document.getElementById('beds24LogModalSubtitle');
    const logChannel = document.getElementById('beds24LogChannel');
    const logOperation = document.getElementById('beds24LogOperation');
    const logStatus = document.getElementById('beds24LogStatus');
    const logExternalId = document.getElementById('beds24LogExternalId');
    const logErrorMessage = document.getElementById('beds24LogErrorMessage');
    const logRequest = document.getElementById('beds24LogRequest');
    const logResponse = document.getElementById('beds24LogResponse');

    const prettyPrint = (value) => {
        if (value === null || value === undefined || value === '') {
            return 'No data captured.';
        }

        if (typeof value === 'string') {
            return value;
        }

        return JSON.stringify(value, null, 2);
    };

    document.querySelectorAll('[data-log-details]').forEach((button) => {
        button.addEventListener('click', function () {
            const details = JSON.parse(button.getAttribute('data-log-details'));
            if (logSubtitle) {
                logSubtitle.textContent = details.created_at ? new Date(details.created_at).toLocaleString() : '';
            }
            if (logChannel) {
                logChannel.textContent = details.channel ?? '';
            }
            if (logOperation) {
                logOperation.textContent = details.operation ?? '';
            }
            if (logStatus) {
                logStatus.textContent = details.status ?? '';
            }
            if (logExternalId) {
                logExternalId.textContent = details.external_id ?? 'N/A';
            }
            if (logErrorMessage) {
                if (details.error_message) {
                    logErrorMessage.textContent = details.error_message;
                    logErrorMessage.classList.remove('d-none');
                } else {
                    logErrorMessage.textContent = '';
                    logErrorMessage.classList.add('d-none');
                }
            }
            if (logRequest) {
                logRequest.textContent = prettyPrint(details.request);
            }
            if (logResponse) {
                logResponse.textContent = prettyPrint(details.response);
            }
            if (logModal) {
                logModal.show();
            }
        });
    });

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
