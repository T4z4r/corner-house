@extends('layouts.admin.app')

@section('title', $room->name)

@section('pageLoader', false)

@push('styles')
<style>
    .room-cal-grid {
        display: grid;
        grid-template-columns: repeat(7, minmax(0, 1fr));
        gap: 0.5rem;
        padding: 1rem;
    }
    .room-cal-weekday {
        padding: 0.25rem 0.35rem;
        color: #6c757d;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }
    .room-cal-day {
        min-height: 130px;
        padding: 0.6rem;
        border: 1px solid rgba(0, 0, 0, 0.08);
        border-radius: 0.75rem;
        background: #fff;
        overflow: hidden;
        cursor: pointer;
        transition: transform 0.15s, box-shadow 0.15s, border-color 0.15s;
    }
    .room-cal-day:hover {
        transform: translateY(-1px);
        border-color: rgba(31, 111, 67, 0.22);
        box-shadow: 0 0.5rem 1rem rgba(33, 37, 41, 0.08);
    }
    .room-cal-day.is-outside { background: #f8f9fa; color: #8a9097; }
    .room-cal-day.is-today { border-color: var(--ch-forest); box-shadow: inset 0 0 0 1px rgba(31, 111, 67, 0.16); }
    .room-cal-day.is-selected { border-color: var(--ch-forest); box-shadow: 0 0 0 0.15rem rgba(31, 111, 67, 0.12); }
    .room-cal-day-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 0.45rem;
    }
    .room-cal-day-number {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 1.8rem;
        height: 1.8rem;
        border-radius: 999px;
        background: rgba(31, 111, 67, 0.08);
        color: var(--ch-forest);
        font-size: 0.85rem;
        font-weight: 700;
    }
    .room-cal-day.is-outside .room-cal-day-number { background: rgba(108, 117, 125, 0.1); color: #6c757d; }
    .room-cal-day-meta { color: #6c757d; font-size: 0.72rem; }
    .room-cal-events { display: flex; flex-direction: column; gap: 0.3rem; }
    .room-cal-event {
        border: 0;
        border-radius: 0.5rem;
        padding: 0.3rem 0.5rem;
        color: #fff;
        font-size: 0.72rem;
        font-weight: 600;
        text-align: left;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .rc-ev-confirmed { background: #1f6f43; }
    .rc-ev-pending { background: #c9a227; }
    .rc-ev-hold { background: #6c757d; }
    .rc-ev-checked-in { background: #0d6efd; }
    .rc-ev-block { background: #20c997; }
    .rc-ev-block-rates { background: #c9a227; }
    .rc-ev-block-restrictions { background: #fd7e14; }
    .rc-ev-block-manual { background: #6c757d; }
    .room-cal-empty {
        min-height: 200px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        color: #6c757d;
        padding: 1rem;
    }
    .room-cal-nav { display: flex; gap: 0.5rem; align-items: center; }
    .room-cal-nav .btn { border-radius: 999px; }
    .room-cal-legend { display: flex; flex-wrap: wrap; gap: 0.75rem; font-size: 0.82rem; }
    .room-cal-legend-item { display: flex; align-items: center; gap: 0.4rem; }
    .room-cal-legend-dot { width: 0.75rem; height: 0.75rem; border-radius: 999px; }
    .rc-day-detail-empty {
        min-height: 120px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        color: #6c757d;
        padding: 1rem;
    }
    .rc-day-swatch { width: 0.75rem; height: 0.75rem; border-radius: 999px; flex-shrink: 0; }
    #dayDetail { max-height: 480px; overflow-y: auto; overflow-x: hidden; }
    #dayDetail .min-w-0 { min-width: 0; }
    .rc-sw-confirmed { background: #1f6f43; }
    .rc-sw-pending { background: #c9a227; }
    .rc-sw-hold { background: #6c757d; }
    .rc-sw-checked-in { background: #0d6efd; }
    .rc-sw-block { background: #20c997; }
    .rc-sw-block-rates { background: #c9a227; }
    .rc-sw-block-restrictions { background: #fd7e14; }
    .rc-sw-block-manual { background: #6c757d; }
    @media (max-width: 768px) {
        .room-cal-grid { gap: 0.35rem; padding: 0.75rem; }
        .room-cal-day { min-height: 100px; padding: 0.5rem; }
    }
</style>
@endpush

@section('content')
    <div class="ch-page-header">
        <div>
            <div class="ch-breadcrumb">
                <a href="{{ route('admin.properties.index') }}">Properties</a> /
                @if ($room->property)
                    <a href="{{ route('admin.rooms.index', $room->property) }}">{{ $room->property->name }}</a> /
                @else
                    <a href="{{ route('admin.rooms.manage') }}">Rooms</a> /
                @endif
                {{ $room->name }}
            </div>
            <h4>{{ $room->name }}</h4>
            <p class="ch-subtitle">{{ $room->type ?: ($room->property?->name ?? 'Room') }}</p>
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
                                @if ($room->property)
                                    <a href="{{ route('admin.properties.show', $room->property) }}" class="text-decoration-none">{{ $room->property->name }}</a>
                                @else
                                    <span class="text-muted">Unassigned</span>
                                @endif
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
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-semibold"><i class="bi bi-calendar3 me-2"></i>Availability</div>
                        <div class="small text-muted">Monthly view for {{ $room->name }}</div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        @can('calendar.manage')
                            <button class="btn btn-sm btn-ch-primary" data-bs-toggle="modal" data-bs-target="#blockModal">
                                <i class="bi bi-calendar-plus me-1"></i>Add Item
                            </button>
                        @endcan
                        <div class="room-cal-nav">
                            <button class="btn btn-outline-secondary btn-sm" id="rcPrev"><i class="bi bi-chevron-left"></i></button>
                            <button class="btn btn-outline-secondary btn-sm" id="rcToday">Today</button>
                            <button class="btn btn-outline-secondary btn-sm" id="rcNext"><i class="bi bi-chevron-right"></i></button>
                        </div>
                        <span class="fw-semibold ms-2 small" id="rcPeriodLabel"></span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="room-cal-legend px-3 pt-3">
                        <div class="room-cal-legend-item"><span class="room-cal-legend-dot" style="background:#1f6f43"></span>Confirmed</div>
                        <div class="room-cal-legend-item"><span class="room-cal-legend-dot" style="background:#c9a227"></span>Pending</div>
                        <div class="room-cal-legend-item"><span class="room-cal-legend-dot" style="background:#0d6efd"></span>Checked in</div>
                        <div class="room-cal-legend-item"><span class="room-cal-legend-dot" style="background:#6c757d"></span>Hold</div>
                        <div class="room-cal-legend-item"><span class="room-cal-legend-dot" style="background:#20c997"></span>Availability</div>
                        <div class="room-cal-legend-item"><span class="room-cal-legend-dot" style="background:#fd7e14"></span>Stay rules</div>
                        <div class="room-cal-legend-item"><span class="room-cal-legend-dot" style="background:#c9a227;opacity:.7"></span>Rates</div>
                    </div>
                    <div class="room-cal-grid" id="rcWeekdays"></div>
                    <div class="room-cal-grid pt-0" id="rcGrid" aria-live="polite"></div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <div class="fw-semibold">Selected day</div>
                    <div class="small text-muted">Tap a day to inspect bookings and blocks.</div>
                </div>
                <div class="card-body" id="dayDetail"></div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent border-bottom">
                    <h6 class="mb-0">Quick Actions</h6>
                </div>
                <div class="card-body d-grid gap-2">
                    <a href="{{ route('admin.rooms.edit', $room) }}" class="btn btn-outline-primary">
                        <i class="bi bi-pencil me-2"></i>Edit Room
                    </a>
                    @if ($room->property)
                        <a href="{{ route('admin.rooms.index', $room->property) }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-2"></i>Back to Rooms
                        </a>
                        <a href="{{ route('admin.properties.show', $room->property) }}" class="btn btn-outline-secondary">
                            <i class="bi bi-building me-2"></i>View Property
                        </a>
                    @endif
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
                            <select name="type" class="form-select no-select2" id="blockType">
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
                            <select name="is_active" class="form-select no-select2" id="blockActive">
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

    const weekdayLabels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    const weekdaysEl = document.getElementById('rcWeekdays');
    const gridEl = document.getElementById('rcGrid');
    const labelEl = document.getElementById('rcPeriodLabel');
    const dayDetailEl = document.getElementById('dayDetail');

    weekdaysEl.innerHTML = weekdayLabels.map(l => '<div class="room-cal-weekday">' + l + '</div>').join('');

    const today = startOfDay(new Date());
    let visibleMonth = startOfMonth(today);
    let selectedDate = today;
    let events = [];

    function startOfDay(d) { const c = new Date(d); c.setHours(0,0,0,0); return c; }
    function startOfMonth(d) { const c = new Date(d); c.setDate(1); c.setHours(0,0,0,0); return c; }
    function addDays(d, n) { const c = new Date(d); c.setDate(c.getDate() + n); return c; }
    function sameMonth(a, b) { return a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth(); }
    function sameDay(a, b) { return a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate(); }
    function dateKey(d) { return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0'); }
    function monthKey(d) { return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0'); }
    function parseLocal(s) { return new Date(s + 'T00:00:00'); }
    function formatMonth(d) { return new Intl.DateTimeFormat('en-GB', { month: 'long', year: 'numeric' }).format(d); }

    function evClass(event) {
        const cn = Array.isArray(event.className) ? (event.className[0]||'') : (event.className||'');
        if (cn.includes('confirmed')) return 'rc-ev-confirmed';
        if (cn.includes('pending')) return 'rc-ev-pending';
        if (cn.includes('hold')) return 'rc-ev-hold';
        if (cn.includes('checked-in')) return 'rc-ev-checked-in';
        if (cn.includes('block-availability') || cn.includes('fc-event--block ')) return 'rc-ev-block';
        if (cn.includes('block-rates') || cn.includes('rates')) return 'rc-ev-block-rates';
        if (cn.includes('block-restrictions') || cn.includes('restrictions')) return 'rc-ev-block-restrictions';
        if (cn.includes('block-manual') || cn.includes('manual')) return 'rc-ev-block-manual';
        return 'rc-ev-block';
    }

    function buildMap() {
        const map = new Map();
        events.forEach(ev => {
            const s = startOfDay(parseLocal(ev.start));
            const e = ev.end ? startOfDay(parseLocal(ev.end)) : s;
            const fin = startOfDay(addDays(e, -1));
            let cur = new Date(s);
            while (cur <= fin) {
                const k = dateKey(cur);
                if (!map.has(k)) map.set(k, []);
                map.get(k).push(ev);
                cur.setDate(cur.getDate() + 1);
            }
        });
        return map;
    }

    function render() {
        labelEl.textContent = formatMonth(visibleMonth);
        const first = startOfMonth(visibleMonth);
        const offset = (first.getDay() + 6) % 7;
        const gridStart = addDays(first, -offset);
        const map = buildMap();
        const cells = [];

        for (let i = 0; i < 42; i++) {
            const d = addDays(gridStart, i);
            const k = dateKey(d);
            const dayEvs = map.get(k) || [];
            const outside = !sameMonth(d, visibleMonth);
            const isToday = sameDay(d, today);
            const isSel = sameDay(d, selectedDate);
            const evMarkup = dayEvs.slice(0,3).map(ev => {
                const roomOk = ev.extendedProps && ev.extendedProps.room_id == roomId;
                return '<button type="button" class="room-cal-event ' + evClass(ev) + '" data-id="' + ev.id + '" title="' + ev.title + '"' + (roomOk ? '' : ' style="opacity:.4"') + '>' + ev.title + '</button>';
            }).join('');
            const extra = dayEvs.length > 3 ? '<div class="room-cal-day-meta">+' + (dayEvs.length-3) + ' more</div>' : '';

            cells.push(
                '<div class="room-cal-day' + (outside ? ' is-outside' : '') + (isToday ? ' is-today' : '') + (isSel ? ' is-selected' : '') + '" data-date="' + k + '">' +
                    '<div class="room-cal-day-header">' +
                        '<div class="room-cal-day-number">' + d.getDate() + '</div>' +
                        '<div class="room-cal-day-meta">' + (outside ? new Intl.DateTimeFormat('en-GB',{month:'short'}).format(d) : '') + '</div>' +
                    '</div>' +
                    '<div class="room-cal-events">' + evMarkup + '</div>' +
                    extra +
                '</div>'
            );
        }

        gridEl.innerHTML = cells.join('');

        gridEl.querySelectorAll('.room-cal-day').forEach(cell => {
            cell.addEventListener('click', () => {
                selectedDate = parseLocal(cell.dataset.date);
                const sInput = document.querySelector('#blockForm input[name="start_date"]');
                const eInput = document.querySelector('#blockForm input[name="end_date"]');
                if (sInput) sInput.value = cell.dataset.date;
                if (eInput) eInput.value = cell.dataset.date;
                render();
            });
        });

        gridEl.querySelectorAll('.room-cal-event').forEach(btn => {
            btn.addEventListener('click', e => {
                e.stopPropagation();
                const ev = events.find(x => x.id === btn.dataset.id);
                if (ev?.extendedProps?.url) window.location = ev.extendedProps.url;
            });
        });

        renderDayDetail();
    }

    function formatDay(d) { return new Intl.DateTimeFormat('en-GB', { day: 'numeric', month: 'long', year: 'numeric' }).format(d); }
    function endDate(ev) { return ev.end ? addDays(parseLocal(ev.end), -1) : parseLocal(ev.start); }

    function renderDayDetail() {
        if (!dayDetailEl) return;
        const k = dateKey(selectedDate);
        const map = buildMap();
        const dayEvs = (map.get(k) || []).slice().sort((a, b) => parseLocal(a.start) - parseLocal(b.start));

        if (!dayEvs.length) {
            dayDetailEl.innerHTML = `
                <div class="rc-day-detail-empty">
                    <div class="display-6 mb-2 text-muted"><i class="bi bi-calendar2-week"></i></div>
                    <p class="mb-1">No bookings or blocks on ${formatDay(selectedDate)}.</p>
                    <p class="small mb-0">Tap another day to inspect it.</p>
                </div>
            `;
            return;
        }

        dayDetailEl.innerHTML = `
            <div class="mb-3">
                <div class="small text-muted">Selected date</div>
                <div class="fw-semibold">${formatDay(selectedDate)}</div>
            </div>
            <div class="d-flex flex-column gap-2">
                ${dayEvs.map((ev) => {
                    const evUrl = ev.extendedProps?.url || null;
                    const swatch = 'rc-sw-' + evClass(ev).replace('rc-ev-', '');
                    const endD = endDate(ev);
                    const dateRange = sameDay(endD, parseLocal(ev.start))
                        ? formatDay(parseLocal(ev.start))
                        : `${formatDay(parseLocal(ev.start))} to ${formatDay(endD)}`;
                    const body = `
                        <div class="small text-muted">${dateRange}</div>
                        <div class="fw-semibold text-truncate" title="${ev.title}">${ev.title}</div>
                    `;
                    const inner = `
                        <div class="d-flex align-items-start gap-2 min-w-0 w-100">
                            <span class="rc-day-swatch ${swatch} mt-1"></span>
                            <div class="flex-grow-1 min-w-0">${body}</div>
                        </div>
                    `;
                    return evUrl
                        ? `<a href="${evUrl}" class="text-decoration-none text-reset border rounded-3 p-3 d-block">${inner}</a>`
                        : `<div class="border rounded-3 p-3">${inner}</div>`;
                }).join('')}
            </div>
        `;
    }

    function loadEvents() {
        const s = dateKey(startOfMonth(visibleMonth));
        const e = dateKey(addDays(new Date(visibleMonth.getFullYear(), visibleMonth.getMonth()+1, 0), 1));
        const url = new URL(eventsUrl, window.location.origin);
        url.searchParams.set('start', s);
        url.searchParams.set('end', e);
        url.searchParams.set('property_id', propertyId);
        url.searchParams.set('room_id', roomId);
        return fetch(url.toString(), { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(p => { events = Array.isArray(p) ? p : []; })
            .catch(() => { events = []; });
    }

    function syncUrl() {
        const url = new URL(window.location.href);
        url.searchParams.set('month', monthKey(visibleMonth));
        window.history.replaceState({}, '', url.toString());
    }

    function updateMonth(delta) {
        visibleMonth = new Date(visibleMonth.getFullYear(), visibleMonth.getMonth() + delta, 1);
        selectedDate = new Date(visibleMonth);
        syncUrl();
        loadEvents().then(render);
    }

    document.getElementById('rcPrev').addEventListener('click', () => updateMonth(-1));
    document.getElementById('rcNext').addEventListener('click', () => updateMonth(1));
    document.getElementById('rcToday').addEventListener('click', () => {
        visibleMonth = startOfMonth(new Date());
        selectedDate = new Date();
        syncUrl();
        loadEvents().then(render);
    });

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
        const sel = document.getElementById('blockType');
        if (!sel) return;
        const f = typeFields[sel.value] || {};
        const fv = document.getElementById('fieldValue');
        const fms = document.getElementById('fieldMinStay');
        const fxs = document.getElementById('fieldMaxStay');
        const fa = document.getElementById('fieldActive');
        if (fv) fv.style.display = f.value ? '' : 'none';
        if (fms) fms.style.display = f.minStay ? '' : 'none';
        if (fxs) fxs.style.display = f.maxStay ? '' : 'none';
        if (fa) fa.style.display = f.active ? '' : 'none';
        if (f.valueLabel) {
            document.getElementById('valueLabel').textContent = f.valueLabel;
            document.getElementById('blockValue').placeholder = f.valuePlaceholder || '';
        }
    }

    const blockType = document.getElementById('blockType');
    if (blockType) { blockType.addEventListener('change', toggleFields); toggleFields(); }

    const blockForm = document.getElementById('blockForm');
    if (blockForm) {
        blockForm.addEventListener('submit', e => {
            e.preventDefault();
            fetch(blocksStoreUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                body: new FormData(blockForm),
            })
            .then(r => r.json())
            .then(data => {
                if (data.ok) {
                    bootstrap.Modal.getInstance(document.getElementById('blockModal')).hide();
                    blockForm.reset();
                    toggleFields();
                    loadEvents().then(render);
                } else {
                    alert('Unable to save block');
                }
            });
        });
    }

    const params = new URLSearchParams(window.location.search);
    const m = params.get('month');
    if (m) {
        const parts = m.split('-');
        if (parts.length === 2) visibleMonth = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, 1);
    }

    loadEvents().then(render);
})();
</script>
@endpush
