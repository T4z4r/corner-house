@extends('layouts.admin.app')

@section('title', $room->name)

@section('pageLoader', false)

@push('styles')
<style>
    .rc-timeline { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
    .rc-timeline th, .rc-timeline td { border: 1px solid #e9ecef; padding: 0; }
    .rc-timeline thead th { background: #f8f9fa; font-weight: 600; text-align: center; padding: 10px 4px; font-size: 0.9rem; }
    .rc-timeline .rc-day-header { min-width: 110px; }
    .rc-cell { height: 72px; position: relative; cursor: pointer; transition: background 0.15s; }
    .rc-cell:hover { background: #f0f0f0; }
    .rc-cell.today { background: #fff8e1; }
    .rc-cell.today::after { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 2px; background: var(--ch-accent); }
    .rc-event { position: absolute; top: 6px; bottom: 6px; left: 3px; right: 3px; border-radius: 5px; font-size: 0.8rem; padding: 4px 8px; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; color: #fff; font-weight: 500; z-index: 1; }
    .rc-event--confirmed { background: #1f6f43; }
    .rc-event--pending { background: #c9a227; }
    .rc-event--hold { background: #6c757d; }
    .rc-event--checked-in { background: #0d6efd; }
    .rc-event--block { background: #20c997; opacity: 0.85; }
    .rc-event--block-rates { background: #c9a227; opacity: 0.85; }
    .rc-event--block-restrictions { background: #fd7e14; opacity: 0.85; }
    .rc-event--block-manual { background: #6c757d; opacity: 0.85; }
    .rc-weekend { background: #fafafa; }
    .rc-nav-btn { padding: 4px 12px; }
    .rc-legend { display: flex; flex-wrap: wrap; gap: 12px; font-size: 0.8rem; }
    .rc-legend-item { display: flex; align-items: center; gap: 5px; }
    .rc-legend-dot { width: 12px; height: 12px; border-radius: 3px; }
</style>
@endpush

@section('content')
    <div class="ch-page-header">
        <div>
            <div class="ch-breadcrumb">
                <a href="{{ route('admin.properties.index') }}">Properties</a> /
                <a href="{{ route('admin.rooms.index', $room->property) }}">{{ $room->property->name }}</a> /
                {{ $room->name }}
            </div>
            <h4>{{ $room->name }}</h4>
            <p class="ch-subtitle">{{ $room->type ?? $room->property->name }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.rooms.edit', $room) }}" class="btn btn-ch-primary">
                <i class="bi bi-pencil me-1"></i>Edit
            </a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            @if ($room->images->isNotEmpty())
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-transparent border-bottom">
                        <h6 class="mb-0">Images ({{ $room->images->count() }})</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            @foreach ($room->images->sortBy('sort_order') as $image)
                                <div class="col-4 col-md-3">
                                    <div class="position-relative">
                                        <img src="{{ asset('storage/'.$image->path) }}" alt="{{ $image->alt }}" class="rounded w-100" style="height: 120px; object-fit: cover;">
                                        @if ($image->is_primary)
                                            <span class="position-absolute top-0 end-0 badge bg-primary m-1">Primary</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent border-bottom">
                    <h6 class="mb-0">Details</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="ch-label">Status</div>
                            <span class="ch-badge ch-badge-{{ $room->status === 'active' ? 'success' : ($room->status === 'maintenance' ? 'warning' : 'muted') }}">
                                <span class="dot"></span>{{ ucfirst($room->status) }}
                            </span>
                        </div>
                        <div class="col-md-4">
                            <div class="ch-label">Property</div>
                            <div class="fw-semibold">
                                <a href="{{ route('admin.properties.show', $room->property) }}" class="text-decoration-none">{{ $room->property->name }}</a>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="ch-label">Type</div>
                            <div class="fw-semibold">{{ $room->type ?? '-' }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="ch-label">Capacity</div>
                            <div class="fw-semibold">{{ $room->capacity ?? '-' }} guests</div>
                        </div>
                        <div class="col-md-4">
                            <div class="ch-label">Sleeps</div>
                            <div class="fw-semibold">{{ $room->sleeps ?? '-' }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="ch-label">Private</div>
                            <div class="fw-semibold">{{ $room->is_private ? 'Yes' : 'No' }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="ch-label">Base Rate</div>
                            <div class="fw-semibold">&pound;{{ number_format($room->base_rate, 2) }}/night</div>
                        </div>
                        <div class="col-md-4">
                            <div class="ch-label">Min Stay</div>
                            <div class="fw-semibold">{{ $room->min_stay ?? '-' }} nights</div>
                        </div>
                        <div class="col-md-4">
                            <div class="ch-label">Max Stay</div>
                            <div class="fw-semibold">{{ $room->max_stay ?? '-' }} nights</div>
                        </div>
                        @if ($room->description)
                            <div class="col-12">
                                <div class="ch-label">Description</div>
                                <div class="text-muted">{{ $room->description }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bi bi-calendar3 me-2"></i>Availability</h6>
                    <div class="d-flex align-items-center gap-2">
                        @can('calendar.manage')
                            <button class="btn btn-sm btn-ch-primary" data-bs-toggle="modal" data-bs-target="#blockModal">
                                <i class="bi bi-calendar-plus me-1"></i>Add block
                            </button>
                        @endcan
                        <button class="btn btn-sm btn-outline-secondary rc-nav-btn" id="rcPrev"><i class="bi bi-chevron-left"></i></button>
                        <button class="btn btn-sm btn-outline-secondary rc-nav-btn" id="rcToday">Today</button>
                        <button class="btn btn-sm btn-outline-secondary rc-nav-btn" id="rcNext"><i class="bi bi-chevron-right"></i></button>
                        <span class="fw-semibold ms-2 small" id="rcPeriodLabel"></span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="rc-legend px-3 pt-3">
                        <div class="rc-legend-item"><span class="rc-legend-dot" style="background:#1f6f43"></span>Confirmed</div>
                        <div class="rc-legend-item"><span class="rc-legend-dot" style="background:#c9a227"></span>Pending</div>
                        <div class="rc-legend-item"><span class="rc-legend-dot" style="background:#0d6efd"></span>Checked in</div>
                        <div class="rc-legend-item"><span class="rc-legend-dot" style="background:#6c757d"></span>Hold</div>
                        <div class="rc-legend-item"><span class="rc-legend-dot" style="background:#20c997"></span>Availability</div>
                        <div class="rc-legend-item"><span class="rc-legend-dot" style="background:#fd7e14"></span>Min/Max Stay</div>
                    </div>
                    <div style="overflow-x: auto;">
                        <table class="rc-timeline" id="rcTimeline">
                            <thead><tr id="rcHead"></tr></thead>
                            <tbody><tr id="rcRow"></tr></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent border-bottom">
                    <h6 class="mb-0">Quick Actions</h6>
                </div>
                <div class="card-body d-grid gap-2">
                    <a href="{{ route('admin.rooms.edit', $room) }}" class="btn btn-outline-primary">
                        <i class="bi bi-pencil me-2"></i>Edit Room
                    </a>
                    <a href="{{ route('admin.rooms.index', $room->property) }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Back to Rooms
                    </a>
                    <a href="{{ route('admin.properties.show', $room->property) }}" class="btn btn-outline-secondary">
                        <i class="bi bi-building me-2"></i>View Property
                    </a>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-bottom">
                    <h6 class="mb-0">Metadata</h6>
                </div>
                <div class="card-body">
                    <div class="ch-label">Created</div>
                    <div class="text-muted mb-2">{{ $room->created_at->format('d M Y H:i') }}</div>
                    <div class="ch-label">Last updated</div>
                    <div class="text-muted">{{ $room->updated_at->format('d M Y H:i') }}</div>
                </div>
            </div>
        </div>
    </div>

    @can('calendar.manage')
        <div class="modal fade" id="blockModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <form id="blockForm" class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add calendar block</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="property_id" value="{{ $room->property_id }}">
                        <input type="hidden" name="room_id" value="{{ $room->id }}">
                        <div class="mb-3">
                            <label class="form-label">Type</label>
                            <select name="type" class="form-select" id="blockType">
                                @foreach (\App\Http\Controllers\Admin\CalendarController::blockTypesPublic() as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3" id="fieldValue" style="display:none">
                            <label class="form-label" id="valueLabel">Value</label>
                            <input type="number" name="value" class="form-control" id="blockValue" step="0.01" min="0" placeholder="Enter value">
                        </div>

                        <div class="mb-3" id="fieldMinStay" style="display:none">
                            <label class="form-label">Min Stay (nights)</label>
                            <input type="number" name="min_stay" class="form-control" id="blockMinStay" min="1" placeholder="e.g. 2">
                        </div>

                        <div class="mb-3" id="fieldMaxStay" style="display:none">
                            <label class="form-label">Max Stay (nights)</label>
                            <input type="number" name="max_stay" class="form-control" id="blockMaxStay" min="1" placeholder="e.g. 14">
                        </div>

                        <div class="mb-3" id="fieldActive" style="display:none">
                            <label class="form-label">Status</label>
                            <select name="is_active" class="form-select" id="blockActive">
                                <option value="1">Open (available for booking)</option>
                                <option value="0">Closed (not available)</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Title <span class="text-muted">(optional)</span></label>
                            <input type="text" name="title" class="form-control" placeholder="e.g. Owner away, Repainting">
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <label class="form-label">Start date</label>
                                <input type="date" name="start_date" class="form-control" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label">End date</label>
                                <input type="date" name="end_date" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button class="btn btn-ch-primary">Save block</button>
                    </div>
                </form>
            </div>
        </div>
    @endcan
@endsection

@push('scripts')
<script>
(function () {
    const roomId = {{ $room->id }};
    const propertyId = {{ $room->property_id }};
    const eventsUrl = '{{ route('admin.calendar.events') }}';
    const blocksStoreUrl = '{{ route('admin.calendar.blocks.store') }}';
    const today = new Date();
    let viewDays = 14;
    let startDate = getWeekStart(today);

    function getWeekStart(date) {
        const d = new Date(date);
        const day = d.getDay();
        d.setDate(d.getDate() - day + (day === 0 ? -6 : 1));
        d.setHours(0, 0, 0, 0);
        return d;
    }

    function addDays(date, days) {
        const d = new Date(date);
        d.setDate(d.getDate() + days);
        return d;
    }

    function fmt(d) { return d.toISOString().split('T')[0]; }

    const typeFields = {
        'availability': { active: true },
        'min_stay': { minStay: true },
        'max_stay': { maxStay: true },
        'daily_price': { value: true, valueLabel: 'Price per night (£)', valuePlaceholder: 'e.g. 150.00' },
        'fixed_prices': { value: true, valueLabel: 'Fixed price (£)', valuePlaceholder: 'e.g. 120.00' },
        'multiplier': { value: true, valueLabel: 'Multiplier', valuePlaceholder: 'e.g. 1.5' },
        'manual': {},
    };

    function toggleFields() {
        const type = document.getElementById('blockType');
        if (!type) return;
        const fields = typeFields[type.value] || {};
        const fv = document.getElementById('fieldValue');
        const fms = document.getElementById('fieldMinStay');
        const fxs = document.getElementById('fieldMaxStay');
        const fa = document.getElementById('fieldActive');
        if (fv) fv.style.display = fields.value ? '' : 'none';
        if (fms) fms.style.display = fields.minStay ? '' : 'none';
        if (fxs) fxs.style.display = fields.maxStay ? '' : 'none';
        if (fa) fa.style.display = fields.active ? '' : 'none';
        if (fields.valueLabel) {
            document.getElementById('valueLabel').textContent = fields.valueLabel;
            document.getElementById('blockValue').placeholder = fields.valuePlaceholder || '';
        }
    }

    const blockType = document.getElementById('blockType');
    if (blockType) {
        blockType.addEventListener('change', toggleFields);
        toggleFields();
    }

    function render() {
        const head = document.getElementById('rcHead');
        const row = document.getElementById('rcRow');
        const label = document.getElementById('rcPeriodLabel');
        const endDate = addDays(startDate, viewDays);

        label.textContent = startDate.toLocaleDateString('en-GB', { day: 'numeric', month: 'short' }) +
            ' – ' + addDays(endDate, -1).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });

        head.innerHTML = '';
        row.innerHTML = '';

        const dates = [];
        for (let d = new Date(startDate); d < endDate; d.setDate(d.getDate() + 1)) {
            dates.push(new Date(d));
        }

        dates.forEach(d => {
            const th = document.createElement('th');
            th.className = 'rc-day-header';
            const dow = d.getDay();
            if (dow === 0 || dow === 6) th.classList.add('rc-weekend');
            if (fmt(d) === fmt(today)) th.style.background = '#fff8e1';
            th.innerHTML = '<div style="font-size:0.75rem">' + d.toLocaleDateString('en-GB', { weekday: 'short' }) + '</div>' +
                '<div style="font-size:0.7rem;color:#6c757d">' + d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short' }) + '</div>';
            head.appendChild(th);

            const td = document.createElement('td');
            td.className = 'rc-cell';
            if (dow === 0 || dow === 6) td.classList.add('rc-weekend');
            if (fmt(d) === fmt(today)) td.classList.add('today');
            td.dataset.date = fmt(d);
            td.addEventListener('click', function () {
                const startInput = document.querySelector('#blockForm input[name="start_date"]');
                const endInput = document.querySelector('#blockForm input[name="end_date"]');
                if (startInput) startInput.value = this.dataset.date;
                if (endInput) endInput.value = this.dataset.date;
                const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('blockModal'));
                modal.show();
            });
            row.appendChild(td);
        });

        loadEvents(dates[0], dates[dates.length - 1]);
    }

    function loadEvents(start, end) {
        const url = eventsUrl + '?property_id=' + propertyId + '&start=' + fmt(start) + '&end=' + fmt(addDays(end, 1));
        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(events => {
                events.forEach(event => {
                    const eStart = new Date(event.start);
                    const eEnd = new Date(event.end);
                    const eRoomId = event.extendedProps.room_id;
                    if (eRoomId != roomId) return;

                    document.querySelectorAll('#rcRow .rc-cell').forEach(td => {
                        const cellDate = new Date(td.dataset.date);
                        if (cellDate >= eStart && cellDate < eEnd) {
                            const div = document.createElement('div');
                            div.className = 'rc-event ' + (event.className || '');
                            div.textContent = event.title;
                            if (event.extendedProps.url) {
                                div.style.cursor = 'pointer';
                                div.addEventListener('click', (e) => {
                                    e.stopPropagation();
                                    window.location = event.extendedProps.url;
                                });
                            }
                            td.appendChild(div);
                        }
                    });
                });
            });
    }

    document.getElementById('rcPrev').addEventListener('click', () => { startDate = addDays(startDate, -viewDays); render(); });
    document.getElementById('rcNext').addEventListener('click', () => { startDate = addDays(startDate, viewDays); render(); });
    document.getElementById('rcToday').addEventListener('click', () => { startDate = getWeekStart(today); render(); });

    const blockForm = document.getElementById('blockForm');
    if (blockForm) {
        blockForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const formData = new FormData(blockForm);
            fetch(blocksStoreUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                body: formData,
            })
            .then(r => r.json())
            .then(data => {
                if (data.ok) {
                    bootstrap.Modal.getInstance(document.getElementById('blockModal')).hide();
                    blockForm.reset();
                    toggleFields();
                    render();
                } else {
                    alert('Unable to save block');
                }
            });
        });
    }

    render();
})();
</script>
@endpush
