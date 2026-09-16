@php
    $tourSteps = collect([
        ['permission' => null, 'title' => 'Welcome to your admin area', 'body' => 'Use the left menu to move between sections. On a phone, open the menu using the menu button. This tour introduces the tools available to your account. You can close it at any time and restart from Quick tour.', 'route' => null],
        ['permission' => 'enquiries.view', 'title' => 'Review enquiries', 'body' => 'Start with new guest enquiries. Open an enquiry to review the requested dates and contact details, then use the available actions to follow up. The sidebar badges show total and pending items.', 'route' => 'admin.enquiries.index'],
        ['permission' => 'reservations.view', 'title' => 'Manage bookings', 'body' => 'Search and filter bookings, then open one to see the guest, stay dates and payment status. Use the Export menu to download the current list or save it as PDF.', 'route' => 'admin.reservations.index'],
        ['permission' => 'calendar.view', 'title' => 'Check availability and prices', 'body' => 'Use the calendar to review bookings, blocked dates and nightly prices. Staff with calendar management permission can add blocks and edit prices. Check the room and date range before saving.', 'route' => 'admin.calendar'],
        ['permission' => 'guests.view', 'title' => 'Find your guests', 'body' => 'Search the guest directory by name, email or phone. Open a profile to see booking history and contact details. Use Export to download guests matching your search.', 'route' => 'admin.guests.index'],
        ['permission' => 'payments.view', 'title' => 'Track payments', 'body' => 'Review pending, paid and refunded payments. Open the related booking for more details. Payment links, refunds and other payment actions are available according to your permissions.', 'route' => 'admin.payments.index'],
        ['permission' => 'communications.view', 'title' => 'Review messages', 'body' => 'Check guest communications and delivery status. Pending messages are awaiting delivery; failed messages may need attention. Staff with sending permission can send messages to guests.', 'route' => 'admin.communications.index'],
        ['permission' => 'settings.view', 'title' => 'Set up notifications and background jobs', 'body' => 'Review your settings, including the host email addresses for booking and payment notifications. Under Cron Jobs, authorised staff can process queued work and inspect the execution output. Keep the server cron enabled for automatic processing.', 'route' => 'admin.settings'],
        ['permission' => null, 'title' => 'You are ready', 'body' => 'Start with the pending items in the sidebar. You can open Quick tour again whenever you need a reminder. Your account permissions determine which actions you can perform.', 'route' => null],
    ])->filter(fn ($step) => $step['permission'] === null || auth()->user()->can($step['permission']))
        ->map(fn ($step) => ['title' => $step['title'], 'body' => $step['body'], 'url' => $step['route'] ? route($step['route']) : null])->values()->all();
@endphp

<div class="modal fade" id="adminQuickTour" tabindex="-1" aria-labelledby="adminTourTitle" aria-describedby="adminTourBody">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="adminTourTitle">Quick tour</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close tour"></button>
            </div>
            <div class="modal-body" aria-live="polite" aria-atomic="true">
                <p id="adminTourProgress" class="small text-muted"></p>
                <p id="adminTourBody"></p>
                <a id="adminTourLink" class="btn btn-outline-primary" hidden>Open this section</a>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light me-auto" data-bs-dismiss="modal">Close tour</button>
                <button type="button" id="adminTourBack" class="btn btn-outline-secondary">Back</button>
                <button type="button" id="adminTourNext" class="btn btn-ch-primary">Next</button>
                <button type="button" id="adminTourFinish" class="btn btn-ch-primary" data-bs-dismiss="modal" hidden>Finish</button>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
(() => {
    const steps = @json($tourSteps);
    const modal = document.getElementById('adminQuickTour');
    const back = document.getElementById('adminTourBack');
    const next = document.getElementById('adminTourNext');
    const finish = document.getElementById('adminTourFinish');
    let index = 0;
    function render() {
        const step = steps[index];
        document.getElementById('adminTourTitle').textContent = step.title;
        document.getElementById('adminTourBody').textContent = step.body;
        document.getElementById('adminTourProgress').textContent = 'Step ' + (index + 1) + ' of ' + steps.length;
        const link = document.getElementById('adminTourLink');
        link.hidden = !step.url;
        if (step.url) link.href = step.url;
        else link.removeAttribute('href');
        back.disabled = index === 0;
        next.hidden = index === steps.length - 1;
        finish.hidden = !next.hidden;
    }
    modal.addEventListener('show.bs.modal', () => { index = 0; render(); });
    back.addEventListener('click', () => { if (index > 0) index--; render(); });
    next.addEventListener('click', () => {
        if (index < steps.length - 1) index++;
        render();
        if (!finish.hidden) finish.focus();
    });
})();
</script>
@endpush
