<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlacesOfInterest;
use App\Services\Audit\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PlacesOfInterestController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function index(Request $request): View
    {
        $query = PlacesOfInterest::query()
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $search = $request->string('search')->toString();

                $query->where(function (Builder $subQuery) use ($search): void {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%")
                        ->orWhere('distance', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('category'), fn (Builder $query) => $query->where('category', $request->string('category')->toString()))
            ->when($request->filled('status'), function (Builder $query) use ($request): void {
                $query->where('is_active', $request->string('status')->toString() === 'active');
            })
            ->orderBy('sort_order')
            ->orderBy('name');

        $items = $query->paginate(20)->withQueryString();

        return view('admin.places.index', [
            'items' => $items,
            'categories' => PlacesOfInterest::query()
                ->select('category')
                ->distinct()
                ->orderBy('category')
                ->pluck('category'),
        ]);
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
            'image' => ['nullable', 'max:5120'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $data['slug'] = Str::slug($data['name']);
        $data['is_active'] = $request->boolean('is_active', true);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('places', 'public');
        } elseif ($request->has('image')) {
            // The dropzone submits a stored path (or empty string when none uploaded).
            $data['image'] = $request->string('image')->toString() ?: null;
        }

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
            'image' => ['nullable', 'max:5120'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $data['slug'] = Str::slug($data['name']);
        $data['is_active'] = $request->boolean('is_active', true);

        if ($request->hasFile('image')) {
            if ($place->image && \Storage::disk('public')->exists($place->image)) {
                \Storage::disk('public')->delete($place->image);
            }
            $data['image'] = $request->file('image')->store('places', 'public');
        } elseif ($request->has('image')) {
            // The dropzone submits a stored path (or empty string when removed).
            $newImage = $request->string('image')->toString() ?: null;

            if ($place->image !== $newImage && $place->image
                && \Storage::disk('public')->exists($place->image)) {
                \Storage::disk('public')->delete($place->image);
            }

            $data['image'] = $newImage;
        }

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

    public function uploadImage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'image', 'max:5120'],
        ]);

        $path = $request->file('file')->store('places', 'public');

        return response()->json([
            'ok' => true,
            'path' => $path,
            'url' => \Storage::disk('public')->url($path),
        ]);
    }

    public function destroyUploadedImage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'path' => ['required', 'string'],
        ]);

        $path = $validated['path'];

        if (! str_starts_with($path, 'places/')) {
            return response()->json(['ok' => false], 422);
        }

        if (\Storage::disk('public')->exists($path)) {
            \Storage::disk('public')->delete($path);
        }

        return response()->json(['ok' => true]);
    }
}
