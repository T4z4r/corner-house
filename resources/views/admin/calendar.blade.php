@extends('layouts.admin.app')

@section('title', 'Calendar')

@section('pageLoader', false)

@push('styles')
<style>
    .fc-event--confirmed { background-color: #1f6f43 !important; border-color: #1f6f43 !important; }
    .fc-event--pending { background-color: #c9a227 !important; border-color: #c9a227 !important; }
    .fc-event--hold { background-color: #6c757d !important; border-color: #6c757d !important; }
    .fc-event--checked-in { background-color: #0d6efd !important; border-color: #0d6efd !important; }
    .fc-event--block { opacity: 0.84; }
    .fc-event--block-availability { background-color: #20c997 !important; border-color: #20c997 !important; }
    .fc-event--block-rates { background-color: #c9a227 !important; border-color: #c9a227 !important; }
    .fc-event--block-restrictions { background-color: #fd7e14 !important; border-color: #fd7e14 !important; }
    .fc-event--block-manual { background-color: #6c757d !important; border-color: #6c757d !important; }
</style>
@endpush

@section('content')
    <div class="ch-page-header">
        <div>
            <div class="ch-breadcrumb">Management / Calendar</div>
            <h4>Calendar</h4>
            <p class="ch-subtitle">Bookings, holds and calendar blocks at a glance</p>
            <div class="text-muted small mt-1">Beds24-style settings: Availability, Min Stay, Max Stay, Daily Price, Fixed Prices, Multiplier.</div>
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
            @can('calendar.manage')
                <button class="btn btn-ch-primary" data-bs-toggle="modal" data-bs-target="#blockModal">
                    <i class="bi bi-calendar-plus me-1"></i>Add block
                </button>
            @endcan
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div id="calendar"></div>
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
                        <input type="hidden" name="property_id" id="block_property" value="{{ $selectedProperty?->id }}">
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
    document.addEventListener('DOMContentLoaded', function () {
        const propertyId = {{ $selectedProperty?->id ?? 'null' }};
        const calendarEl = document.getElementById('calendar');
        const { Calendar, dayGridPlugin, timeGridPlugin, listPlugin, interactionPlugin } = window.FullCalendar;

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

        document.getElementById('blockType').addEventListener('change', toggleFields);
        toggleFields();

        const calendar = new Calendar(calendarEl, {
            plugins: [dayGridPlugin, timeGridPlugin, listPlugin, interactionPlugin],
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,dayGridWeek,dayGridDay,listWeek',
            },
            events: (info, successCallback, failureCallback) => {
                fetch('{{ route('admin.calendar.events') }}?property_id=' + propertyId + '&start=' + info.startStr + '&end=' + info.endStr, {
                    headers: { 'Accept': 'application/json' }
                })
                .then(r => r.json())
                .then(successCallback)
                .catch(failureCallback);
            },
            eventClick: (info) => {
                if (info.event.extendedProps.type === 'reservation' && info.event.extendedProps.url) {
                    window.location = info.event.extendedProps.url;
                }
            },
            dateClick: (info) => {
                const startInput = document.querySelector('#blockForm input[name="start_date"]');
                const endInput = document.querySelector('#blockForm input[name="end_date"]');
                if (startInput && endInput) {
                    startInput.value = info.dateStr;
                    endInput.value = info.dateStr;
                    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('blockModal'));
                    modal.show();
                }
            },
        });
        calendar.render();

        const propertyFilter = document.getElementById('propertyFilter');
        if (propertyFilter) {
            propertyFilter.addEventListener('change', () => {
                const url = new URL(window.location.href);
                url.searchParams.set('property_id', propertyFilter.value);
                window.location = url.toString();
            });
        }

        const blockForm = document.getElementById('blockForm');
        if (blockForm) {
            blockForm.addEventListener('submit', (e) => {
                e.preventDefault();
                const formData = new FormData(blockForm);
                fetch('{{ route('admin.calendar.blocks.store') }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                    body: formData,
                })
                .then(r => r.json())
                .then((data) => {
                    if (data.ok) {
                        bootstrap.Modal.getInstance(document.getElementById('blockModal')).hide();
                        calendar.refetchEvents();
                        blockForm.reset();
                        toggleFields();
                    } else {
                        alert('Unable to save block');
                    }
                });
            });
        }
    });
</script>
@endpush
