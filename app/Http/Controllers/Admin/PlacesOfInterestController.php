<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlacesOfInterest;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PlacesOfInterestController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function index(): View
    {
        $items = PlacesOfInterest::query()->orderBy('sort_order')->orderBy('name')->get();

        return view('admin.places.index', ['items' => $items]);
    }

    public function create(): View
    {
        return view('admin.places.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['required', 'string', 'in:attraction,town,nature,activity,shop,transport,other'],
            'address' => ['nullable', 'string', 'max:500'],
            'distance' => ['nullable', 'string', 'max:100'],
            'website' => ['nullable', 'url', 'max:500'],
            'phone' => ['nullable', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $data['slug'] = Str::slug($data['name']);
        $data['is_active'] = $request->boolean('is_active', true);

        PlacesOfInterest::create($data);
        $this->auditLogger->log('places_of_interest.created', 'places_of_interests', 'places_of_interest', $data['slug']);

        return redirect()->route('admin.places.index')->with('status', 'Place created.');
    }

    public function edit(PlacesOfInterest $place): View
    {
        return view('admin.places.edit', ['item' => $place]);
    }

    public function update(Request $request, PlacesOfInterest $place): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['required', 'string', 'in:attraction,town,nature,activity,shop,transport,other'],
            'address' => ['nullable', 'string', 'max:500'],
            'distance' => ['nullable', 'string', 'max:100'],
            'website' => ['nullable', 'url', 'max:500'],
            'phone' => ['nullable', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $data['slug'] = Str::slug($data['name']);
        $data['is_active'] = $request->boolean('is_active', true);

        $place->update($data);
        $this->auditLogger->log('places_of_interest.updated', 'places_of_interests', 'places_of_interest', (string) $place->id);

        return redirect()->route('admin.places.index')->with('status', 'Place updated.');
    }

    public function destroy(PlacesOfInterest $place): RedirectResponse
    {
        $place->delete();
        $this->auditLogger->log('places_of_interest.deleted', 'places_of_interests', 'places_of_interest', (string) $place->id);

        return redirect()->route('admin.places.index')->with('status', 'Place deleted.');
    }

    public function toggle(PlacesOfInterest $place): RedirectResponse
    {
        $place->update(['is_active' => ! $place->is_active]);
        $this->auditLogger->log('places_of_interest.toggled', 'places_of_interests', 'places_of_interest', (string) $place->id);

        return back()->with('status', 'Status updated.');
    }
}
