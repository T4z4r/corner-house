<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Amenity;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AmenityController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function index(Request $request): View
    {
        $query = Amenity::query()->withCount('properties');

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });
        }

        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }

        if ($request->query('status') === 'active') {
            $query->where('is_active', true);
        } elseif ($request->query('status') === 'inactive') {
            $query->where('is_active', false);
        }

        $amenities = $query->orderBy('category')->orderBy('name')->paginate(20)->withQueryString();
        $categories = Amenity::query()->distinct()->whereNotNull('category')->pluck('category')->sort()->values();

        return view('admin.amenities.index', [
            'amenities' => $amenities,
            'categories' => $categories,
        ]);
    }

    public function create(): View
    {
        return view('admin.amenities.create', [
            'categories' => Amenity::query()->distinct()->whereNotNull('category')->pluck('category')->sort()->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $amenity = Amenity::create($this->validated($request));

        $this->auditLogger->log('amenities.created', 'amenities', 'amenity', (string) $amenity->id, newValues: ['name' => $amenity->name]);

        return redirect()->route('admin.amenities.index')->with('status', 'Amenity created.');
    }

    public function edit(Amenity $amenity): View
    {
        return view('admin.amenities.edit', [
            'amenity' => $amenity,
            'categories' => Amenity::query()->distinct()->whereNotNull('category')->pluck('category')->sort()->values(),
        ]);
    }

    public function update(Request $request, Amenity $amenity): RedirectResponse
    {
        $old = $amenity->only(['name', 'icon', 'description', 'category', 'is_active']);
        $amenity->update($this->validated($request));
        $new = $amenity->only(['name', 'icon', 'description', 'category', 'is_active']);

        $this->auditLogger->log('amenities.updated', 'amenities', 'amenity', (string) $amenity->id, $old, $new);

        return redirect()->route('admin.amenities.index')->with('status', 'Amenity updated.');
    }

    public function toggle(Amenity $amenity): RedirectResponse
    {
        $amenity->update(['is_active' => ! $amenity->is_active]);

        $this->auditLogger->log(
            $amenity->is_active ? 'amenities.activated' : 'amenities.deactivated',
            'amenities',
            'amenity',
            (string) $amenity->id,
            ['is_active' => ! $amenity->is_active],
            ['is_active' => $amenity->is_active],
        );

        return back()->with('status', "Amenity {$amenity->name} ".($amenity->is_active ? 'activated' : 'deactivated').'.');
    }

    public function destroy(Amenity $amenity): RedirectResponse
    {
        $this->auditLogger->log('amenities.deleted', 'amenities', 'amenity', (string) $amenity->id, newValues: ['name' => $amenity->name]);
        $amenity->delete();

        return redirect()->route('admin.amenities.index')->with('status', 'Amenity deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'category' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }
}
