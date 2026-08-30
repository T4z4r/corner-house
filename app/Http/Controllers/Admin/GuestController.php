<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Guest;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GuestController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function index(Request $request): View
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

        $guests = $query->orderBy('last_name')->paginate(15)->withQueryString();

        return view('admin.guests.index', ['guests' => $guests]);
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
