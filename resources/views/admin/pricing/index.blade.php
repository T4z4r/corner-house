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
                <form method="POST" action="{{ route('admin.pricing.ai.generate') }}" class="d-inline">
                    @csrf
                    <input type="hidden" name="property_id" value="{{ $selectedPropertyId }}">
                    <button class="btn btn-outline-success" onclick="return confirm('Generate AI seasonal pricing rules for this property?');">
                        <i class="bi bi-stars me-1"></i>Generate seasonal pricing
                    </button>
                </form>
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
            <button class="nav-link active" id="rules-tab" data-bs-toggle="tab" data-bs-target="#rules" type="button" role="tab">Rules ({{ $rules->total() }})</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="overrides-tab" data-bs-toggle="tab" data-bs-target="#overrides" type="button" role="tab">Rate overrides ({{ $overrides->total() }})</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="preview-tab" data-bs-toggle="tab" data-bs-target="#preview" type="button" role="tab">Daily price preview</button>
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
                                        <td>
                                            {{ $rule->adjustment_type === 'percent' ? $rule->adjustment_value.'%' : '£'.number_format($rule->adjustment_value, 0) }}
                                            @if ($rule->generated_by_ai)
                                                <span class="badge text-bg-success ms-1">AI</span>
                                            @endif
                                        </td>
                                        <td class="small">
                                            @if ($rule->recurring)
                                                Annually: {{ $rule->start_date?->format('d M') ?? 'always' }} -> {{ $rule->end_date?->format('d M') ?? 'open' }}
                                                <span class="badge text-bg-info ms-1">Recurring</span>
                                            @elseif ($rule->start_date)
                                                {{ $rule->start_date->format('d M Y') }} -> {{ $rule->end_date?->format('d M Y') ?? 'open' }}
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
                                                  data-confirm="Delete this rule?">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
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
                    <div class="card-body border-top py-2">
                        {{ $rules->links() }}
                    </div>
                </div>
            </div>
        </div>

        @foreach ($rules as $rule)
            <div class="modal fade" id="editRule{{ $rule->id }}" tabindex="-1" aria-labelledby="editRule{{ $rule->id }}Label" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                    <form method="POST" action="{{ route('admin.pricing.rules.update', $rule) }}" class="modal-content">
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                            <h5 class="modal-title" id="editRule{{ $rule->id }}Label">Edit rule</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label class="form-label">Name *</label>
                                    <input type="text" name="name" class="form-control" value="{{ $rule->name }}" required>
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label">Rule type</label>
                                    <input type="text" class="form-control" value="{{ ucfirst(str_replace('_', ' ', $rule->rule_type)) }}" disabled>
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label">Start date <span class="text-muted small">(optional)</span></label>
                                    <input type="date" name="start_date" class="form-control" value="{{ $rule->start_date?->format('Y-m-d') }}">
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label">End date</label>
                                    <input type="date" name="end_date" class="form-control" value="{{ $rule->end_date?->format('Y-m-d') }}">
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label">Priority *</label>
                                    <input type="number" name="priority" class="form-control" min="1" max="10" value="{{ $rule->priority }}" required>
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label">Adjustment *</label>
                                    <div class="input-group">
                                        <select name="adjustment_type" class="form-select">
                                            <option value="percent" @selected($rule->adjustment_type === 'percent')>%</option>
                                            <option value="multiplier" @selected($rule->adjustment_type === 'multiplier')>x</option>
                                            <option value="amount" @selected($rule->adjustment_type === 'amount')>&pound;</option>
                                        </select>
                                        <input type="number" name="adjustment_value" step="0.01" class="form-control" value="{{ $rule->adjustment_value }}" required>
                                    </div>
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label">Minimum stay</label>
                                    <input type="number" name="minimum_stay" min="1" class="form-control" value="{{ $rule->minimum_stay }}">
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label">Maximum stay</label>
                                    <input type="number" name="max_stay" min="1" class="form-control" value="{{ $rule->max_stay }}">
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label">Occupancy threshold %</label>
                                    <input type="number" name="occupancy_threshold" min="0" max="100" class="form-control" value="{{ $rule->occupancy_threshold }}">
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label">Days before check-in</label>
                                    <input type="number" name="days_before_checkin" min="0" class="form-control" value="{{ $rule->days_before_checkin }}">
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-check-label form-label d-block">&nbsp;</label>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="apply_weekends_only" value="1" @checked($rule->apply_weekends_only)>
                                        <label class="form-check-label">Weekends only</label>
                                    </div>
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-check-label form-label d-block">&nbsp;</label>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="recurring" value="1" @checked($rule->recurring)>
                                        <label class="form-check-label">Recurring annually</label>
                                    </div>
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-check-label form-label d-block">&nbsp;</label>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="is_enabled" value="1" @checked($rule->is_enabled)>
                                        <label class="form-check-label">Enabled</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                            <button class="btn btn-ch-primary">Save changes</button>
                        </div>
                    </form>
                </div>
            </div>
        @endforeach

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
                                        <td>{{ $override->start_date->format('d M Y') }} -> {{ $override->end_date->format('d M Y') }}</td>
                                        <td>&pound;{{ number_format($override->rate, 2) }}</td>
                                        <td>{{ $override->minimum_stay ?? '-' }}</td>
                                        <td class="text-end">
                                            <form method="POST" action="{{ route('admin.pricing.overrides.destroy', $override) }}" class="d-inline"
                                                  data-confirm="Delete this override?">
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
                    <div class="card-body border-top py-2">
                        {{ $overrides->links() }}
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="preview" role="tabpanel">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <form method="GET" class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label" for="previewRoom">Room *</label>
                            <select id="previewRoom" name="room_id" class="form-select" required>
                                <option value="">Select a room&hellip;</option>
                                @foreach ($rooms as $previewOption)
                                    <option value="{{ $previewOption->id }}" @selected(isset($previewRoom) && $previewRoom->id === $previewOption->id)>
                                        {{ $previewOption->property?->name ?? 'Unassigned' }} - {{ $previewOption->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="previewFrom">From</label>
                            <input type="date" id="previewFrom" name="date_from" class="form-control" value="{{ $previewFrom }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="previewTo">To</label>
                            <input type="date" id="previewTo" name="date_to" class="form-control" value="{{ $previewTo }}">
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-ch-primary w-100"><i class="bi bi-search me-1"></i>Preview</button>
                        </div>
                    </form>
                    <div class="text-muted small mt-2">
                        <i class="bi bi-info-circle me-1"></i>
                        Weekday is Mon&ndash;Thu, weekend is Fri&ndash;Sun, and an uplift day is a weekend inside a UK bank-holiday, festive or school-holiday period with the uplift enabled. The range is capped at 120 nights.
                    </div>
                </div>
            </div>

            @if (isset($previewRoom) && $preview)
                <div class="row g-3 mb-3">
                    @php
                        $previewCards = [
                            'weekday' => ['Weekdays night avg', 'ch-badge-muted'],
                            'weekend' => ['Weekends night avg', 'ch-badge-primary'],
                            'uplift' => ['Uplift days night avg', 'ch-badge-warning'],
                        ];
                    @endphp
                    @foreach ($previewCards as $category => [$label, $badge])
                        @php $summary = $previewSummary[$category] ?? null; @endphp
                        @if ($summary)
                            <div class="col-md-4">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="small fw-semibold text-muted">{{ $label }}</span>
                                            <span class="ch-badge {{ $badge }}">{{ $summary['count'] }} nights</span>
                                        </div>
                                        <div class="fs-4 fw-bold">&pound;{{ number_format($summary['avg'], 2) }}</div>
                                        <div class="text-muted small">&pound;{{ number_format($summary['min'], 2) }} &ndash; &pound;{{ number_format($summary['max'], 2) }}</div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Day</th>
                                        <th>Type</th>
                                        <th class="text-end">Price / night</th>
                                        <th>Source</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $typeBadges = [
                                            'weekday' => 'ch-badge-muted',
                                            'weekend' => 'ch-badge-primary',
                                            'uplift' => 'ch-badge-warning',
                                        ];
                                    @endphp
                                    @foreach ($preview as $row)
                                        <tr>
                                            <td>{{ $row['date']->format('d M Y') }}</td>
                                            <td class="fw-semibold">{{ $row['date']->format('l') }}</td>
                                            <td>
                                                <span class="ch-badge {{ $typeBadges[$row['category']] ?? 'ch-badge-muted' }}">{{ ucfirst($row['category']) }}</span>
                                            </td>
                                            <td class="text-end fw-semibold">&pound;{{ number_format($row['price'], 2) }}</td>
                                            <td class="small">
                                                @if ($row['source'] === 'override')
                                                    Manual override
                                                @elseif ($row['source'] === 'calendar_block')
                                                    Calendar rate
                                                @elseif ($row['source'] === 'rule')
                                                    Rule: {{ ucfirst(str_replace('_', ' ', $row['rule_type'])) }}
                                                @else
                                                    Base rate
                                                @endif
                                                @if ($row['uplift_applied'])
                                                    <span class="badge text-bg-warning ms-1">+{{ number_format((float) \App\Models\Setting::getValue('holiday_weekend_uplift', 5), 0) }}% uplift</span>
                                                @endif
                                                @if ($row['min_floor'])
                                                    <span class="badge text-bg-secondary ms-1">min floor &pound;{{ number_format($row['min_price'], 2) }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @else
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3 text-muted">
                            <i class="bi bi-calendar3 fs-3"></i>
                            <div>
                                <div class="fw-semibold">No preview yet</div>
                                <div class="small">Pick a room and date range to see the nightly price per date, split across weekdays, weekends and uplift days.</div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @can('pricing.create')
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
                                        <option value="{{ $room->id }}">{{ $room->property?->name ?? 'Unassigned' }} - {{ $room->name }}</option>
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
                            <div class="col-6 mb-3">
                                <label class="form-check-label form-label d-block">&nbsp;</label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="recurring" value="1">
                                    <label class="form-check-label">Recurring annually</label>
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
                                    <option value="{{ $room->id }}">{{ $room->property?->name ?? 'Unassigned' }} - {{ $room->name }}</option>
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
