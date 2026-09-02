@extends('layouts.admin.app')

@section('title', 'Calendar')

@section('pageLoader', false)

@push('styles')
<style>
    .calendar-layout {
        display: grid;
        grid-template-columns: minmax(0, 1.7fr) minmax(320px, 0.9fr);
        gap: 1rem;
        min-width: 0;
    }

    .calendar-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 1rem;
        border-bottom: 1px solid rgba(0, 0, 0, 0.06);
        background: linear-gradient(180deg, rgba(13, 110, 253, 0.04), rgba(255, 255, 255, 0));
    }

    .calendar-title {
        font-size: 1.2rem;
        font-weight: 700;
        letter-spacing: -0.02em;
    }

    .calendar-controls {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        align-items: center;
    }

    .calendar-controls .btn {
        border-radius: 999px;
    }

    .calendar-grid {
        display: grid;
        grid-template-columns: repeat(7, minmax(0, 1fr));
        gap: 0.5rem;
        padding: 1rem;
        min-width: 0;
    }

    .calendar-weekday {
        padding: 0.25rem 0.35rem;
        color: #6c757d;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }

    .calendar-day {
        min-height: 150px;
        padding: 0.7rem;
        border: 1px solid rgba(0, 0, 0, 0.08);
        border-radius: 1rem;
        background: #fff;
        overflow: hidden;
        cursor: pointer;
        transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
    }

    .calendar-day:hover {
        transform: translateY(-1px);
        border-color: rgba(13, 110, 253, 0.22);
        box-shadow: 0 0.6rem 1.2rem rgba(33, 37, 41, 0.08);
    }

    .calendar-day.is-outside {
        background: #f8f9fa;
        color: #8a9097;
    }

    .calendar-day.is-today {
        border-color: #0d6efd;
        box-shadow: inset 0 0 0 1px rgba(13, 110, 253, 0.16);
    }

    .calendar-day.is-selected {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.15rem rgba(13, 110, 253, 0.12);
    }

    .calendar-day-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
        margin-bottom: 0.55rem;
    }

    .calendar-day-number {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2rem;
        height: 2rem;
        border-radius: 999px;
        background: rgba(13, 110, 253, 0.08);
        color: #0d6efd;
        font-size: 0.95rem;
        font-weight: 700;
    }

    .calendar-day.is-outside .calendar-day-number {
        background: rgba(108, 117, 125, 0.1);
        color: #6c757d;
    }

    .calendar-day-meta {
        color: #6c757d;
        font-size: 0.78rem;
    }

    .calendar-events {
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
    }

    .calendar-event {
        border: 0;
        border-radius: 0.7rem;
        padding: 0.35rem 0.55rem;
        color: #fff;
        font-size: 0.78rem;
        font-weight: 600;
        text-align: left;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .event-confirmed { background: #1f6f43; }
    .event-pending { background: #c9a227; }
    .event-hold { background: #6c757d; }
    .event-checked-in { background: #0d6efd; }
    .event-block-availability { background: #20c997; }
    .event-block-rates { background: #c9a227; }
    .event-block-restrictions { background: #fd7e14; }
    .event-block-manual { background: #6c757d; }

    .calendar-side {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        min-width: 0;
    }

    .calendar-side .card {
        overflow: hidden;
    }

    .calendar-side .card-header {
        background: linear-gradient(180deg, rgba(13, 110, 253, 0.04), rgba(255, 255, 255, 0));
    }

    .calendar-side .card-body {
        overflow-y: auto;
        max-height: 400px;
    }

    .calendar-empty {
        min-height: 240px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        color: #6c757d;
        padding: 1rem;
    }

    .legend-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 0.4rem 0;
        font-size: 0.9rem;
    }

    .legend-swatch {
        width: 0.85rem;
        height: 0.85rem;
        border-radius: 999px;
        flex: 0 0 auto;
    }

    @media (max-width: 1199px) {
        .calendar-layout {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 768px) {
        .calendar-grid {
            gap: 0.35rem;
            padding: 0.75rem;
        }

        .calendar-day {
            min-height: 120px;
            padding: 0.55rem;
        }
    }
</style>
@endpush

@section('content')
    @php
        $selectedPropertyId = $selectedProperty?->id;
        $selectedRoomId = $selectedRoomId ?? '';
    @endphp

    <div class="ch-page-header">
        <div>
            <div class="ch-breadcrumb">Management / Calendar</div>
            <h4>Calendar</h4>
            <p class="ch-subtitle mb-0">Custom property calendar for reservations, holds, and Beds24-aligned blocks.</p>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-building text-muted"></i>
                <select id="propertyFilter" class="form-select" style="width: auto; min-width: 180px;">
                    @foreach ($properties as $property)
                        <option value="{{ $property->id }}" @selected($selectedProperty?->id === $property->id)>{{ $property->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-door-open text-muted"></i>
                <select id="roomFilter" class="form-select" style="width: auto; min-width: 160px;">
                    <option value="">All rooms</option>
                    @foreach ($rooms as $room)
                        <option value="{{ $room->id }}" @selected($selectedRoomId == $room->id)>{{ $room->name }}</option>
                    @endforeach
                </select>
            </div>
            @can('calendar.manage')
                <button class="btn btn-ch-primary" data-bs-toggle="modal" data-bs-target="#blockModal">
                    <i class="bi bi-calendar-plus me-1"></i>Add Item
                </button>
            @endcan
        </div>
    </div>

    <div class="calendar-layout">
        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="calendar-toolbar">
                <div>
                    <div class="text-uppercase text-muted small fw-semibold">Calendar view</div>
                    <div id="monthLabel" class="calendar-title"></div>
                </div>
                <div class="calendar-controls">
                    <button class="btn btn-outline-secondary btn-sm" type="button" id="prevMonth"><i class="bi bi-chevron-left"></i></button>
                    <button class="btn btn-outline-secondary btn-sm" type="button" id="todayButton">Today</button>
                    <button class="btn btn-outline-secondary btn-sm" type="button" id="nextMonth"><i class="bi bi-chevron-right"></i></button>
                </div>
            </div>

            <div class="calendar-grid" id="calendarWeekdays" aria-hidden="true"></div>
            <div class="calendar-grid pt-0" id="calendarGrid" aria-live="polite"></div>
        </div>

        <div class="calendar-side">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <div class="fw-semibold">Selected day</div>
                    <div class="small text-muted">Tap a day to inspect bookings and blocks.</div>
                </div>
                <div class="card-body" id="dayDetail"></div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <div class="fw-semibold">Legend</div>
                    <div class="small text-muted">Color coding used in the custom calendar.</div>
                </div>
                <div class="card-body">
                    <div class="legend-item"><span><span class="legend-swatch event-confirmed me-2"></span>Confirmed booking</span><span class="small text-muted">Reservation</span></div>
                    <div class="legend-item"><span><span class="legend-swatch event-pending me-2"></span>Pending booking</span><span class="small text-muted">Reservation</span></div>
                    <div class="legend-item"><span><span class="legend-swatch event-hold me-2"></span>Hold</span><span class="small text-muted">Temporary block</span></div>
                    <div class="legend-item"><span><span class="legend-swatch event-block-availability me-2"></span>Availability</span><span class="small text-muted">Block</span></div>
                    <div class="legend-item"><span><span class="legend-swatch event-block-rates me-2"></span>Rate change</span><span class="small text-muted">Block</span></div>
                    <div class="legend-item"><span><span class="legend-swatch event-block-restrictions me-2"></span>Stay rules</span><span class="small text-muted">Block</span></div>
                    <div class="legend-item"><span><span class="legend-swatch event-block-manual me-2"></span>Manual</span><span class="small text-muted">Block</span></div>
                </div>
            </div>
        </div>
    </div>

    @can('calendar.manage')
        <div class="modal fade" id="blockModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <form id="blockForm" class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="blockModalTitle">Add calendar block</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="property_id" id="block_property" value="{{ $selectedPropertyId }}">
                        <input type="hidden" name="block_id" id="blockId">
                        <div class="mb-3">
                            <label class="form-label">Room <span class="text-muted">(optional)</span></label>
                            <select name="room_id" id="blockRoom" class="form-select">
                                <option value="">All rooms</option>
                                @foreach ($rooms as $room)
                                    <option value="{{ $room->id }}">{{ $room->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Type</label>
                            <select name="type" class="form-select" id="blockType">
                                @foreach ($blockTypes as $value => $label)
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
                            <input type="text" name="title" id="blockTitle" class="form-control" placeholder="e.g. Owner away, Repainting">
                        </div>

                        <div class="row">
                            <div class="col-6">
                                <label class="form-label">Start date</label>
                                <input type="date" name="start_date" id="blockStartDate" class="form-control" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label">End date</label>
                                <input type="date" name="end_date" id="blockEndDate" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <div class="me-auto d-flex gap-2" id="blockModifyActions" style="display:none !important;">
                            <button type="button" class="btn btn-outline-danger btn-sm" id="blockDeleteButton"><i class="bi bi-trash me-1"></i>Delete</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="blockToggleButton">Toggle</button>
                        </div>
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button class="btn btn-ch-primary" id="blockSaveButton">Save block</button>
                    </div>
                </form>
            </div>
        </div>
    @endcan
@endsection

@push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const propertyId = @json($selectedPropertyId);
        const selectedRoomId = @json($selectedRoomId);
        const initialMonth = @json($initialMonth);
        const eventsEndpoint = @json(route('admin.calendar.events'));
        const blocksStoreEndpoint = @json(route('admin.calendar.blocks.store'));
        const blockUpdateTemplate = @json(route('admin.calendar.blocks.update', ['block' => '__ID__']));
        const blockToggleTemplate = @json(route('admin.calendar.blocks.toggle', ['block' => '__ID__']));
        const blockDestroyTemplate = @json(route('admin.calendar.blocks.destroy', ['block' => '__ID__']));
        const roomsData = @json($rooms->map(fn($r) => ['id' => $r->id, 'name' => $r->name]));
        const today = startOfDay(new Date());
        let visibleMonth = startOfMonth(parseMonthKey(initialMonth));
        let selectedDate = isSameMonth(today, visibleMonth) ? today : new Date(visibleMonth);
        let events = [];
        let activePropertyId = propertyId || '';
        let activeRoomId = selectedRoomId || '';
        let editingBlockId = null;

        const weekdayLabels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        const monthLabelEl = document.getElementById('monthLabel');
        const gridEl = document.getElementById('calendarGrid');
        const weekdaysEl = document.getElementById('calendarWeekdays');
        const dayDetailEl = document.getElementById('dayDetail');

        weekdaysEl.innerHTML = weekdayLabels.map((label) => `<div class="calendar-weekday">${label}</div>`).join('');

        function parseMonthKey(monthKey) {
            return new Date(`${monthKey}-01T00:00:00`);
        }

        function parseLocalDate(value) {
            return new Date(`${value}T00:00:00`);
        }

        function startOfDay(date) {
            const copy = new Date(date);
            copy.setHours(0, 0, 0, 0);
            return copy;
        }

        function startOfMonth(date) {
            const copy = new Date(date);
            copy.setDate(1);
            copy.setHours(0, 0, 0, 0);
            return copy;
        }

        function endOfMonth(date) {
            const copy = new Date(date);
            copy.setMonth(copy.getMonth() + 1, 0);
            copy.setHours(23, 59, 59, 999);
            return copy;
        }

        function addDays(date, days) {
            const copy = new Date(date);
            copy.setDate(copy.getDate() + days);
            return copy;
        }

        function monthKey(date) {
            return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
        }

        function dateKey(date) {
            return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
        }

        function isSameMonth(a, b) {
            return a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth();
        }

        function isSameDay(a, b) {
            return a.getFullYear() === b.getFullYear()
                && a.getMonth() === b.getMonth()
                && a.getDate() === b.getDate();
        }

        function formatMonthLabel(date) {
            return new Intl.DateTimeFormat('en-GB', {
                month: 'long',
                year: 'numeric',
            }).format(date);
        }

        function formatDayLabel(date) {
            return new Intl.DateTimeFormat('en-GB', {
                weekday: 'long',
                day: 'numeric',
                month: 'long',
            }).format(date);
        }

        function formatEventDate(date) {
            return new Intl.DateTimeFormat('en-GB', {
                day: 'numeric',
                month: 'short',
            }).format(date);
        }

        function eventClassName(event) {
            const className = event.className;

            if (Array.isArray(className)) {
                return className[0] || '';
            }

            return typeof className === 'string' ? className : '';
        }

        function eventBadgeClass(event) {
            const className = eventClassName(event);

            if (className.includes('confirmed')) {
                return 'event-confirmed';
            }

            if (className.includes('pending')) {
                return 'event-pending';
            }

            if (className.includes('hold')) {
                return 'event-hold';
            }

            if (className.includes('checked-in')) {
                return 'event-checked-in';
            }

            if (className.includes('availability')) {
                return 'event-block-availability';
            }

            if (className.includes('rates')) {
                return 'event-block-rates';
            }

            if (className.includes('restrictions')) {
                return 'event-block-restrictions';
            }

            return 'event-block-manual';
        }

        function eventLabel(event) {
            const type = event.extendedProps?.type;

            if (type === 'reservation') {
                return `Booking - ${event.title}`;
            }

            if (type === 'hold') {
                return `Hold - ${event.title}`;
            }

            return event.title;
        }

        function buildEventMap() {
            const map = new Map();

            events.forEach((event) => {
                const start = parseLocalDate(event.start);
                const end = event.end ? parseLocalDate(event.end) : parseLocalDate(event.start);
                const cursor = startOfDay(start);
                const finalDay = event.end ? startOfDay(addDays(end, -1)) : startOfDay(start);

                while (cursor <= finalDay) {
                    const key = dateKey(cursor);
                    if (! map.has(key)) {
                        map.set(key, []);
                    }
                    map.get(key).push(event);
                    cursor.setDate(cursor.getDate() + 1);
                }
            });

            map.forEach((dayEvents) => {
                dayEvents.sort((a, b) => parseLocalDate(a.start) - parseLocalDate(b.start));
            });

            return map;
        }

        function renderDayDetail(dayEvents, date) {
            if (! dayEvents.length) {
                dayDetailEl.innerHTML = `
                    <div class="calendar-empty">
                        <div class="display-6 mb-2 text-muted"><i class="bi bi-calendar2-week"></i></div>
                        <p class="mb-1">No events on ${formatDayLabel(date)}.</p>
                        <p class="small mb-0">Use the month view to pick another day or add a block from here.</p>
                    </div>
                `;
                return;
            }

            dayDetailEl.innerHTML = `
                <div class="mb-3">
                    <div class="small text-muted">Selected date</div>
                    <div class="fw-semibold">${formatDayLabel(date)}</div>
                </div>
                <div class="d-flex flex-column gap-2">
                    ${dayEvents.map((event) => {
                        const openUrl = event.extendedProps?.url || null;
                        const colorClass = eventBadgeClass(event);
                        const label = eventLabel(event);
                        const roomName = event.extendedProps?.room_name || '';
                        const dateRange = event.end
                            ? `${formatEventDate(parseLocalDate(event.start))} to ${formatEventDate(parseLocalDate(event.end))}`
                            : formatEventDate(parseLocalDate(event.start));
                        const body = `
                            <div class="small text-muted">${dateRange}</div>
                            <div class="fw-semibold text-truncate">${label}</div>
                            ${roomName ? `<div class="small text-muted"><i class="bi bi-door-open me-1"></i>${roomName}</div>` : ''}
                        `;

                        if (openUrl) {
                            return `
                                <a href="${openUrl}" class="text-decoration-none text-reset border rounded-3 p-3 d-block">
                                    <div class="d-flex align-items-start gap-2">
                                        <span class="legend-swatch ${colorClass} mt-1"></span>
                                        <div class="flex-grow-1">${body}</div>
                                    </div>
                                </a>
                            `;
                        }

                        return `
                            <div class="border rounded-3 p-3">
                                <div class="d-flex align-items-start gap-2">
                                    <span class="legend-swatch ${colorClass} mt-1"></span>
                                    <div class="flex-grow-1">${body}</div>
                                </div>
                            </div>
                        `;
                    }).join('')}
                </div>
            `;
        }

        function renderMonth() {
            monthLabelEl.textContent = formatMonthLabel(visibleMonth);

            const firstVisible = startOfMonth(visibleMonth);
            const weekStartOffset = (firstVisible.getDay() + 6) % 7;
            const gridStart = addDays(firstVisible, -weekStartOffset);
            const map = buildEventMap();
            const cells = [];

            for (let index = 0; index < 42; index++) {
                const cellDate = addDays(gridStart, index);
                const dayKey = dateKey(cellDate);
                const dayEvents = map.get(dayKey) || [];
                const isOutside = ! isSameMonth(cellDate, visibleMonth);
                const isToday = isSameDay(cellDate, today);
                const isSelected = isSameDay(cellDate, selectedDate);
                const dayEventsMarkup = dayEvents.slice(0, 3).map((event) => `
                    <button type="button" class="calendar-event ${eventBadgeClass(event)}" data-event-id="${event.id}" title="${event.title}">
                        ${eventLabel(event)}
                    </button>
                `).join('');
                const extraCount = dayEvents.length > 3 ? `<div class="calendar-day-meta">+${dayEvents.length - 3} more</div>` : '';

                cells.push(`
                    <div class="calendar-day ${isOutside ? 'is-outside' : ''} ${isToday ? 'is-today' : ''} ${isSelected ? 'is-selected' : ''}" data-date="${dayKey}">
                        <div class="calendar-day-header">
                            <div class="calendar-day-number">${cellDate.getDate()}</div>
                            <div class="calendar-day-meta">${isOutside ? new Intl.DateTimeFormat('en-GB', { month: 'short' }).format(cellDate) : ''}</div>
                        </div>
                        <div class="calendar-events">${dayEventsMarkup}</div>
                        ${extraCount}
                    </div>
                `);
            }

            gridEl.innerHTML = cells.join('');

            const selectedDayEvents = map.get(dateKey(selectedDate)) || [];
            renderDayDetail(selectedDayEvents, selectedDate);

            gridEl.querySelectorAll('.calendar-day').forEach((cell) => {
                cell.addEventListener('click', () => {
                    selectedDate = parseLocalDate(cell.dataset.date);
                    renderMonth();
                });
            });

            gridEl.querySelectorAll('.calendar-event').forEach((button) => {
                button.addEventListener('click', (event) => {
                    event.stopPropagation();
                    const matched = events.find((item) => item.id === button.dataset.eventId);

                    if (! matched) {
                        return;
                    }

                    if (matched.extendedProps?.url) {
                        window.location = matched.extendedProps.url;
                        return;
                    }

                    if (matched.extendedProps?.type === 'block' && matched.extendedProps?.block_id) {
                        openBlockEditor(matched);
                    }
                });
            });
        }

        function syncUrl() {
            const url = new URL(window.location.href);
            url.searchParams.set('month', monthKey(visibleMonth));

            if (activePropertyId) {
                url.searchParams.set('property_id', activePropertyId);
            }

            if (activeRoomId) {
                url.searchParams.set('room_id', activeRoomId);
            } else {
                url.searchParams.delete('room_id');
            }

            window.history.replaceState({}, '', url.toString());
        }

        function loadEvents() {
            const start = dateKey(startOfMonth(visibleMonth));
            const end = dateKey(endOfMonth(visibleMonth));
            const url = new URL(eventsEndpoint, window.location.origin);
            url.searchParams.set('start', start);
            url.searchParams.set('end', end);

            if (activePropertyId) {
                url.searchParams.set('property_id', activePropertyId);
            }

            if (activeRoomId) {
                url.searchParams.set('room_id', activeRoomId);
            }

            return fetch(url.toString(), {
                headers: { 'Accept': 'application/json' },
            })
                .then((response) => response.json())
                .then((payload) => {
                    events = Array.isArray(payload) ? payload : [];
                })
                .catch(() => {
                    events = [];
                });
        }

        function updateMonth(delta) {
            visibleMonth = new Date(visibleMonth.getFullYear(), visibleMonth.getMonth() + delta, 1);
            selectedDate = new Date(visibleMonth);
            syncUrl();
            loadEvents().then(renderMonth);
        }

        document.getElementById('prevMonth').addEventListener('click', () => updateMonth(-1));
        document.getElementById('nextMonth').addEventListener('click', () => updateMonth(1));
        document.getElementById('todayButton').addEventListener('click', () => {
            visibleMonth = startOfMonth(new Date());
            selectedDate = new Date();
            syncUrl();
            loadEvents().then(renderMonth);
        });

        const propertyFilter = document.getElementById('propertyFilter');
        if (propertyFilter) {
            propertyFilter.addEventListener('change', () => {
                const url = new URL(window.location.href);
                url.searchParams.set('property_id', propertyFilter.value);
                url.searchParams.set('month', monthKey(visibleMonth));
                url.searchParams.delete('room_id');
                window.location = url.toString();
            });
        }

        const roomFilter = document.getElementById('roomFilter');
        if (roomFilter) {
            roomFilter.addEventListener('change', () => {
                activeRoomId = roomFilter.value;
                syncUrl();
                loadEvents().then(renderMonth);
            });
        }

        const blockRoom = document.getElementById('blockRoom');
        const blockPropertyInput = document.getElementById('block_property');
        if (blockPropertyInput) {
            blockPropertyInput.addEventListener('change', () => {
                const pid = blockPropertyInput.value;
                const matchingRooms = roomsData.filter((r) => true);
                blockRoom.innerHTML = '<option value="">All rooms</option>';
                matchingRooms.forEach((room) => {
                    blockRoom.innerHTML += `<option value="${room.id}">${room.name}</option>`;
                });
            });
        }

        const typeFields = {
            availability: { active: true },
            min_stay: { minStay: true },
            max_stay: { maxStay: true },
            daily_price: { value: true, valueLabel: 'Price per night (GBP)', valuePlaceholder: 'e.g. 150.00' },
            fixed_prices: { value: true, valueLabel: 'Fixed price (GBP)', valuePlaceholder: 'e.g. 120.00' },
            multiplier: { value: true, valueLabel: 'Multiplier', valuePlaceholder: 'e.g. 1.5' },
            manual: {},
        };

        function toggleFields() {
            const type = document.getElementById('blockType').value;
            const fields = typeFields[type] || {};

            document.getElementById('fieldValue').style.display = fields.value ? '' : 'none';
            document.getElementById('fieldMinStay').style.display = fields.minStay ? '' : 'none';
            document.getElementById('fieldMaxStay').style.display = fields.maxStay ? '' : 'none';
            document.getElementById('fieldActive').style.display = fields.active ? '' : 'none';

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

        const blockForm = document.getElementById('blockForm');
        const blockModalTitle = document.getElementById('blockModalTitle');
        const blockModifyActions = document.getElementById('blockModifyActions');
        const blockDeleteButton = document.getElementById('blockDeleteButton');
        const blockToggleButton = document.getElementById('blockToggleButton');

        function setModalMode(editing) {
            editingBlockId = editing ? Number(document.getElementById('blockId').value) : null;
            blockModalTitle.textContent = editing ? 'Edit calendar block' : 'Add calendar block';
            blockModifyActions.style.display = editing ? 'flex' : 'none';
            document.getElementById('blockSaveButton').textContent = editing ? 'Update block' : 'Save block';
        }

        function openBlockEditor(matchedEvent) {
            const props = matchedEvent.extendedProps;

            document.getElementById('blockId').value = props.block_id;
            document.getElementById('block_property').value = props.room_id ? '' : (activePropertyId || '');
            document.getElementById('blockRoom').value = props.room_id || '';
            document.getElementById('blockType').value = props.block_type || 'availability';
            document.getElementById('blockTitle').value = props.block_title || '';
            document.getElementById('blockValue').value = props.block_value ?? '';
            document.getElementById('blockMinStay').value = props.block_min_stay ?? '';
            document.getElementById('blockMaxStay').value = props.block_max_stay ?? '';
            document.getElementById('blockActive').value = props.block_active ? '1' : '0';
            document.getElementById('blockStartDate').value = dateKey(parseLocalDate(matchedEvent.start));
            const endDate = parseLocalDate(matchedEvent.end);
            endDate.setDate(endDate.getDate() - 1);
            document.getElementById('blockEndDate').value = dateKey(endDate);

            toggleFields();
            setModalMode(true);
            bootstrap.Modal.getOrCreateInstance(document.getElementById('blockModal')).show();
        }

        if (blockDeleteButton) {
            blockDeleteButton.addEventListener('click', () => {
                if (! editingBlockId) {
                    return;
                }
                if (! confirm('Delete this block?')) {
                    return;
                }
                fetch(blockDestroyTemplate.replace('__ID__', editingBlockId), {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                })
                    .then((response) => response.json())
                    .then((data) => {
                        if (data.ok) {
                            bootstrap.Modal.getInstance(document.getElementById('blockModal')).hide();
                            blockForm.reset();
                            toggleFields();
                            setModalMode(false);
                            loadEvents().then(renderMonth);
                        }
                    });
            });
        }

        if (blockToggleButton) {
            blockToggleButton.addEventListener('click', () => {
                if (! editingBlockId) {
                    return;
                }
                fetch(blockToggleTemplate.replace('__ID__', editingBlockId), {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                })
                    .then((response) => response.json())
                    .then((data) => {
                        if (data.ok) {
                            bootstrap.Modal.getInstance(document.getElementById('blockModal')).hide();
                            blockForm.reset();
                            toggleFields();
                            setModalMode(false);
                            loadEvents().then(renderMonth);
                        }
                    });
            });
        }

        if (blockForm) {
            blockForm.addEventListener('submit', (event) => {
                event.preventDefault();
                const formData = new FormData(blockForm);
                const blockId = document.getElementById('blockId').value;

                const request = blockId
                    ? {
                        url: blockUpdateTemplate.replace('__ID__', blockId),
                        method: 'POST',
                    }
                    : {
                        url: blocksStoreEndpoint,
                        method: 'POST',
                    };

                fetch(request.url, {
                    method: request.method,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: formData,
                })
                    .then((response) => response.json())
                    .then((data) => {
                        if (data.ok) {
                            const modal = bootstrap.Modal.getInstance(document.getElementById('blockModal'));
                            if (modal) {
                                modal.hide();
                            }
                            blockForm.reset();
                            toggleFields();
                            setModalMode(false);
                            loadEvents().then(renderMonth);
                        } else {
                            alert('Unable to save block');
                        }
                    });
            });
        }

        loadEvents().then(() => {
            renderMonth();
            syncUrl();
        });
    });
</script>
@endpush
