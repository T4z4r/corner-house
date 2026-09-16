<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Guest;
use App\Services\Audit\AuditLogger;
use App\Services\Notification\NotificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GuestController extends Controller
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly NotificationService $notifications,
    ) {}

    public function index(Request $request): View
    {
        return view('admin.guests.index', [
            'guests' => $this->guestQuery($request)->paginate(15)->withQueryString(),
        ]);
    }

    public function export(Request $request): StreamedResponse|View
    {
        $request->validate(['format' => ['sometimes', 'in:csv,html'], 'search' => ['nullable', 'string', 'max:255']]);
        $query = $this->guestQuery($request);

        if ($request->query('format') === 'html') {
            return view('admin.guests.export-pdf', [
                'guests' => $query->get(),
                'search' => $request->query('search'),
            ]);
        }

        return response()->streamDownload(function () use ($query): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Name', 'Email', 'Phone', 'Bookings', 'Source', 'Status']);
            foreach ($query->lazy() as $guest) {
                $row = [$guest->full_name, $guest->email ?? '', $guest->phone ?? '', $guest->reservations_count, $guest->source ?? '', ucfirst($guest->status)];
                fputcsv($handle, array_map(static function (string|int $value): string|int {
                    if (is_string($value) && preg_match('/^[\s]*[=+@-]/u', $value)) {
                        return "'".$value;
                    }

                    return $value;
                }, $row));
            }
            fclose($handle);
        }, 'guests-'.now()->format('Y-m-d-H-i').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function guestQuery(Request $request): Builder
    {
        $query = Guest::query()->withCount('reservations');

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search): void {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('last_name')->orderBy('first_name')->orderBy('id');
    }

    public function create(): View
    {
        return view('admin.guests.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $guest = Guest::create($this->validated($request));

        $this->auditLogger->log('guests.created', 'guests', 'guest', (string) $guest->id, newValues: ['email' => $guest->email]);

        return redirect()->route('admin.guests.show', $guest)->with('status', 'Guest created.');
    }

    public function show(Guest $guest): View
    {
        return view('admin.guests.show', [
            'guest' => $guest->load(['reservations.room', 'communications']),
        ]);
    }

    public function sendEmail(Request $request, Guest $guest): RedirectResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
        ]);

        if (! $guest->email) {
            return back()->withErrors(['email' => 'This guest has no email address on file.']);
        }

        $this->notifications->sendManual([
            'guest_id' => $guest->id,
            'channel' => 'email',
            'recipient' => $guest->email,
            'sender_name' => null,
            'subject' => $data['subject'],
            'body' => $data['body'],
        ]);

        $this->auditLogger->log('guests.email_sent', 'guests', 'guest', (string) $guest->id, newValues: ['recipient' => $guest->email]);

        return back()->with('status', 'Email queued and sent to '.$guest->email.'.');
    }

    public function edit(Guest $guest): View
    {
        return view('admin.guests.edit', ['guest' => $guest]);
    }

    public function update(Request $request, Guest $guest): RedirectResponse
    {
        $old = $guest->only(['first_name', 'last_name', 'email', 'phone', 'status']);
        $guest->update($this->validated($request));
        $new = $guest->only(['first_name', 'last_name', 'email', 'phone', 'status']);

        $this->auditLogger->log('guests.updated', 'guests', 'guest', (string) $guest->id, $old, $new);

        return redirect()->route('admin.guests.show', $guest)->with('status', 'Guest updated.');
    }

    public function destroy(Guest $guest): RedirectResponse
    {
        $this->auditLogger->log('guests.deleted', 'guests', 'guest', (string) $guest->id, newValues: ['email' => $guest->email]);
        $guest->delete();

        return redirect()->route('admin.guests.index')->with('status', 'Guest deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'country' => ['nullable', 'string', 'max:2'],
            'language' => ['nullable', 'string', 'max:5'],
            'preferences' => ['nullable', 'array'],
            'notes' => ['nullable', 'string'],
            'status' => ['required', 'in:active,inactive,blacklisted'],
            'source' => ['nullable', 'string', 'max:100'],
        ]);
    }
}
