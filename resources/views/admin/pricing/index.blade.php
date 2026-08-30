@extends('layouts.admin.app')

@section('title', 'Pricing')

@section('content')
    <div class="ch-page-header">
        <div>
            <div class="ch-breadcrumb">Revenue / Pricing</div>
            <h4>Pricing</h4>
            <p class="ch-subtitle">Rules, overrides and rate management</p>
            <div class="text-muted small mt-1">Pricing rules now support multiplier adjustments plus minimum and maximum stay controls. Publish to Beds24 from Channels > Integrations.</div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @can('pricing.create')
                <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#overrideModal">
                    <i class="bi bi-sliders me-1"></i>New override
                </button>
                <button class="btn btn-ch-primary" data-bs-toggle="modal" data-bs-target="#ruleModal">
                    <i class="bi bi-plus-lg me-1"></i>New rule
                </button>
            @endcan
        </div>
    </div>

    <div class="ch-toolbar mb-3 d-flex align-items-center gap-2 flex-wrap">
        <form method="GET" class="d-flex gap-2 align-items-center">
            <span class="text-muted small fw-semibold">Property:</span>
            <select name="property_id" class="form-select w-auto" onchange="this.form.submit()">
                <option value="">All properties</option>
                @foreach ($properties as $property)
                    <option value="{{ $property->id }}" @selected($selectedPropertyId == $property->id)>{{ $property->name }}</option>
                @endforeach
            </select>
        </form>
        <span class="text-muted small ms-auto d-none d-md-inline"><i class="bi bi-info-circle me-1"></i>Priority: manual &gt; event &gt; holiday &gt; seasonal &gt; occupancy &gt; demand &gt; competitor &gt; base</span>
    </div>

    <ul class="nav nav-tabs" id="pricingTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="rules-tab" data-bs-toggle="tab" data-bs-target="#rules" type="button" role="tab">Rules ({{ $rules->count() }})</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="overrides-tab" data-bs-toggle="tab" data-bs-target="#overrides" type="button" role="tab">Rate overrides ({{ $overrides->count() }})</button>
        </li>
    </ul>

    <div class="tab-content" id="pricingTabContent">
        <div class="tab-pane fade show active" id="rules" role="tabpanel">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Priority</th><th>Name</th><th>Type</th><th>Adjustment</th>
                                    <th>Dates</th><th>Room</th><th>Status</th><th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($rules as $rule)
                                    <tr>
                                        <td><span class="ch-badge ch-badge-primary">{{ $rule->priority }}</span></td>
                                        <td class="fw-semibold">{{ $rule->name }}</td>
                                        <td><span class="ch-badge ch-badge-muted">{{ ucfirst(str_replace('_', ' ', $rule->rule_type)) }}</span></td>
                                        <td>{{ $rule->adjustment_type === 'percent' ? $rule->adjustment_value.'%' : '£'.number_format($rule->adjustment_value, 0) }}</td>
                                        <td class="small">
                                            @if ($rule->start_date)
                                                {{ $rule->start_date->format('d M Y') }} → {{ $rule->end_date?->format('d M Y') ?? 'open' }}
                                            @else
                                                <span class="text-muted">Always</span>
                                            @endif
                                        </td>
                                        <td>{{ $rule->room?->name ?? ($rule->property?->name ?? 'All') }}</td>
                                        <td>
                                            <form method="POST" action="{{ route('admin.pricing.rules.update', $rule) }}" class="d-inline">
                                                @method('PUT')
                                                <input type="hidden" name="name" value="{{ $rule->name }}">
                                                <input type="hidden" name="priority" value="{{ $rule->priority }}">
                                                <input type="hidden" name="adjustment_type" value="{{ $rule->adjustment_type }}">
                                                <input type="hidden" name="adjustment_value" value="{{ $rule->adjustment_value }}">
                                                <input type="hidden" name="is_enabled" value="{{ $rule->is_enabled ? '1' : '0' }}">
                                                <button class="btn btn-sm btn-outline-{{ $rule->is_enabled ? 'success' : 'secondary' }}">
                                                    {{ $rule->is_enabled ? 'Enabled' : 'Disabled' }}
                                                </button>
                                            </form>
                                        </td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editRule{{ $rule->id }}">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <form method="POST" action="{{ route('admin.pricing.rules.destroy', $rule) }}" class="d-inline"
                                                  onsubmit="return confirm('Delete this rule?');">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>

                                    <div class="modal fade" id="editRule{{ $rule->id }}" tabindex="-1">
                                        <div class="modal-dialog">
                                            <form method="POST" action="{{ route('admin.pricing.rules.update', $rule) }}" class="modal-content">
                                                @csrf
                                                @method('PUT')
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Edit rule</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label">Name</label>
                                                        <input type="text" name="name" class="form-control" value="{{ $rule->name }}" required>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-6 mb-3">
                                                            <label class="form-label">Start date</label>
                                                            <input type="date" name="start_date" class="form-control" value="{{ $rule->start_date?->format('Y-m-d') }}">
                                                        </div>
                                                        <div class="col-6 mb-3">
                                                            <label class="form-label">End date</label>
                                                            <input type="date" name="end_date" class="form-control" value="{{ $rule->end_date?->format('Y-m-d') }}">
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-6 mb-3">
                                                            <label class="form-label">Priority</label>
                                                            <input type="number" name="priority" class="form-control" min="1" max="10" value="{{ $rule->priority }}" required>
                                                        </div>
                                                        <div class="col-6 mb-3">
                                                            <label class="form-label">Adjustment</label>
                                                            <div class="input-group">
                                                                <select name="adjustment_type" class="form-select">
                                                                    <option value="percent" @selected($rule->adjustment_type === 'percent')>%</option>
                                                                    <option value="multiplier" @selected($rule->adjustment_type === 'multiplier')>x</option>
                                                                    <option value="amount" @selected($rule->adjustment_type === 'amount')>&pound;</option>
                                                                </select>
                                                                <input type="number" name="adjustment_value" step="0.01" class="form-control" value="{{ $rule->adjustment_value }}" required>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-6 mb-3">
                                                            <label class="form-label">Minimum stay</label>
                                                            <input type="number" name="minimum_stay" min="1" class="form-control" value="{{ $rule->minimum_stay }}">
                                                        </div>
                                                        <div class="col-6 mb-3">
                                                            <label class="form-label">Maximum stay</label>
                                                            <input type="number" name="max_stay" min="1" class="form-control" value="{{ $rule->max_stay }}">
                                                        </div>
                                                        <div class="col-6 mb-3">
                                                            <label class="form-label">Occupancy %</label>
                                                            <input type="number" name="occupancy_threshold" min="0" max="100" class="form-control" value="{{ $rule->occupancy_threshold }}">
                                                        </div>
                                                    </div>
                                                    <div class="form-check form-switch mb-2">
                                                        <input class="form-check-input" type="checkbox" name="is_enabled" value="1" @checked($rule->is_enabled)>
                                                        <label class="form-check-label">Enabled</label>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                                    <button class="btn btn-ch-primary">Save</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                @empty
                                    @include('layouts.admin._empty', [
                                        'icon' => 'bi-tags',
                                        'message' => 'No pricing rules yet',
                                        'hint' => 'Create a rule to automate seasonal, holiday or occupancy-based pricing.',
                                        'colspan' => 8,
                                    ])
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="overrides" role="tabpanel">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr><th>Room</th><th>Dates</th><th>Rate / night</th><th>Min stay</th><th class="text-end">Actions</th></tr>
                            </thead>
                            <tbody>
                                @forelse ($overrides as $override)
                                    <tr>
                                        <td class="fw-semibold">{{ $override->room?->name }}</td>
                                        <td>{{ $override->start_date->format('d M Y') }} → {{ $override->end_date->format('d M Y') }}</td>
                                        <td>&pound;{{ number_format($override->rate, 2) }}</td>
                                        <td>{{ $override->minimum_stay ?? '-' }}</td>
                                        <td class="text-end">
                                            <form method="POST" action="{{ route('admin.pricing.overrides.destroy', $override) }}" class="d-inline"
                                                  onsubmit="return confirm('Delete this override?');">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    @include('layouts.admin._empty', [
                                        'icon' => 'bi-sliders',
                                        'message' => 'No rate overrides',
                                        'hint' => 'Set a manual rate for specific dates to override automated pricing.',
                                        'colspan' => 5,
                                    ])
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @can('pricing.manage')
        <div class="modal fade" id="ruleModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <form method="POST" action="{{ route('admin.pricing.rules.store') }}" class="modal-content">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">New pricing rule</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label">Name *</label>
                                <input type="text" name="name" class="form-control" placeholder="e.g. Summer high season" required>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Rule type *</label>
                                <select name="rule_type" class="form-select" required>
                                    @foreach (['base', 'seasonal', 'holiday', 'occupancy', 'demand', 'competitor', 'event', 'last_minute', 'length_of_stay', 'weekday'] as $type)
                                        <option value="{{ $type }}">{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Property</label>
                                <select name="property_id" class="form-select">
                                    <option value="">All properties</option>
                                    @foreach ($properties as $property)
                                        <option value="{{ $property->id }}">{{ $property->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Room</label>
                                <select name="room_id" class="form-select">
                                    <option value="">All rooms</option>
                                    @foreach ($rooms as $room)
                                        <option value="{{ $room->id }}">{{ $room->property->name }} - {{ $room->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Start date <span class="text-muted small">(optional)</span></label>
                                <input type="date" name="start_date" class="form-control">
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">End date</label>
                                <input type="date" name="end_date" class="form-control">
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Priority *</label>
                                <input type="number" name="priority" class="form-control" min="1" max="10" value="5" required>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Adjustment *</label>
                                <div class="input-group">
                                    <select name="adjustment_type" class="form-select">
                                        <option value="percent">%</option>
                                        <option value="multiplier">x</option>
                                        <option value="amount">&pound;</option>
                                    </select>
                                    <input type="number" name="adjustment_value" step="0.01" class="form-control" placeholder="10" required>
                                </div>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Minimum stay</label>
                                <input type="number" name="minimum_stay" min="1" class="form-control">
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Maximum stay</label>
                                <input type="number" name="max_stay" min="1" class="form-control">
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Occupancy threshold %</label>
                                <input type="number" name="occupancy_threshold" min="0" max="100" class="form-control">
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Days before check-in</label>
                                <input type="number" name="days_before_checkin" min="0" class="form-control">
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-check-label form-label d-block">&nbsp;</label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="apply_weekends_only" value="1">
                                    <label class="form-check-label">Weekends only</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button class="btn btn-ch-primary">Create rule</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="modal fade" id="overrideModal" tabindex="-1">
            <div class="modal-dialog">
                <form method="POST" action="{{ route('admin.pricing.overrides.store') }}" class="modal-content">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">New rate override</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Room *</label>
                            <select name="room_id" class="form-select" required>
                                @foreach ($rooms as $room)
                                    <option value="{{ $room->id }}">{{ $room->property->name }} - {{ $room->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label">Start date *</label>
                                <input type="date" name="start_date" class="form-control" required>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">End date *</label>
                                <input type="date" name="end_date" class="form-control" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label">Rate / night *</label>
                                <input type="number" name="rate" step="0.01" min="0" class="form-control" required>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Minimum stay</label>
                                <input type="number" name="minimum_stay" min="1" class="form-control">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button class="btn btn-ch-primary">Create override</button>
                    </div>
                </form>
            </div>
        </div>
    @endcan
@endsection


