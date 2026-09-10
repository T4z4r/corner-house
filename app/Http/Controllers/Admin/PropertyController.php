<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Amenity;
use App\Models\Property;
use App\Models\PropertyImage;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PropertyController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function index(): View
    {
        $properties = Property::query()
            ->withCount('rooms')
            ->with('linkedProperty:id,name')
            ->orderByRaw('is_primary DESC, name ASC')
            ->paginate(15);

        return view('admin.properties.index', ['properties' => $properties]);
    }

    public function create(): View
    {
        return view('admin.properties.create', [
            'amenities' => Amenity::where('is_active', true)->orderBy('name')->get(),
            'linkedProperties' => Property::query()->whereKeyNot(0)->orderBy('name')->get(),
        ]);
    }

    public function show(Property $property): View
    {
        return view('admin.properties.show', [
            'property' => $property->load(['rooms.images', 'amenities', 'policies', 'images', 'linkedProperty']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $property = Property::create($data);

        $this->applyPrimaryFlag($property);
        $this->applyLink($property, $data['linked_property_id'] ?? null);

        if ($request->has('amenity_ids')) {
            $property->amenities()->sync($request->input('amenity_ids'));
        }

        $this->auditLogger->log('properties.created', 'properties', 'property', (string) $property->id, newValues: $data);

        return redirect()->route('admin.properties.edit', $property)->with('status', 'Property created.');
    }

    public function edit(Property $property): View
    {
        return view('admin.properties.edit', [
            'property' => $property->load(['amenities', 'policies', 'rooms', 'linkedProperty']),
            'amenities' => Amenity::where('is_active', true)->orderBy('name')->get(),
            'linkedProperties' => Property::query()->whereKeyNot($property->id)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Property $property): RedirectResponse
    {
        $old = $property->only([
            'name', 'status', 'city', 'postcode', 'capacity', 'bedrooms', 'bathrooms',
        ]);

        $data = $this->validated($request, $property);
        $previousLink = $property->linked_property_id;
        $property->update($data);

        $this->applyPrimaryFlag($property);
        $this->applyLink($property, $data['linked_property_id'] ?? null, $previousLink);

        if ($request->has('amenity_ids')) {
            $property->amenities()->sync($request->input('amenity_ids'));
        }

        $new = $property->only(['name', 'status', 'city', 'postcode', 'capacity', 'bedrooms', 'bathrooms']);
        $this->auditLogger->log('properties.updated', 'properties', 'property', (string) $property->id, $old, $new);

        return redirect()->route('admin.properties.edit', $property)->with('status', 'Property updated.');
    }

    public function destroy(Property $property): RedirectResponse
    {
        $this->auditLogger->log('properties.deleted', 'properties', 'property', (string) $property->id, newValues: ['name' => $property->name]);
        $property->delete();

        return redirect()->route('admin.properties.index')->with('status', 'Property deleted.');
    }

    public function uploadImage(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'image', 'max:5120'],
            'property_id' => ['required', 'integer', 'exists:properties,id'],
        ]);

        $file = $request->file('file');
        $path = $file->store('properties/'.$request->property_id, 'public');

        $image = PropertyImage::create([
            'property_id' => $request->property_id,
            'path' => $path,
            'alt' => $file->getClientOriginalName(),
            'sort_order' => PropertyImage::where('property_id', $request->property_id)->max('sort_order') + 1,
        ]);

        $this->auditLogger->log('properties.images.uploaded', 'properties', 'property', (string) $request->property_id, newValues: ['path' => $path]);

        return response()->json([
            'ok' => true,
            'image_id' => $image->id,
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
        ]);
    }

    public function destroyImage(PropertyImage $image): JsonResponse
    {
        Storage::disk('public')->delete($image->path);
        $propertyId = $image->property_id;

        $this->auditLogger->log('properties.images.deleted', 'properties', 'property', (string) $propertyId, newValues: ['path' => $image->path]);

        $image->delete();

        return response()->json(['ok' => true]);
    }

    private function validated(Request $request, ?Property $property = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'address_line_1' => ['nullable', 'string'],
            'address_line_2' => ['nullable', 'string'],
            'city' => ['nullable', 'string'],
            'postcode' => ['nullable', 'string'],
            'country' => ['nullable', 'string', 'max:2'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'bedrooms' => ['nullable', 'integer', 'min:0'],
            'bathrooms' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'in:active,inactive,maintenance'],
            'is_primary' => ['nullable', 'boolean'],
            'linked_property_id' => ['nullable', 'integer', 'exists:properties,id'],
            'smoking_allowed' => ['nullable', 'boolean'],
            'children_allowed' => ['nullable', 'boolean'],
            'parties_allowed' => ['nullable', 'boolean'],
            'pets_allowed' => ['nullable', 'string', 'in:yes,upon_request,no'],
            'check_in_from' => ['nullable', 'string', 'max:10'],
            'check_in_until' => ['nullable', 'string', 'max:10'],
            'check_out_from' => ['nullable', 'string', 'max:10'],
            'check_out_until' => ['nullable', 'string', 'max:10'],
            'custom_rules' => ['nullable', 'string'],
        ]);

        $existingSlug = $property?->slug ?? Str::slug($request->input('name'));

        return array_merge($data, [
            'slug' => $existingSlug ?? Str::slug($request->input('name')),
            'is_primary' => $request->boolean('is_primary'),
            'linked_property_id' => $request->input('linked_property_id') ?: null,
        ]);
    }

    /**
     * Ensure only one property in the whole system carries the primary flag.
     */
    private function applyPrimaryFlag(Property $property): void
    {
        if ($property->is_primary) {
            Property::whereKeyNot($property->id)->update(['is_primary' => false]);
        }
    }

    /**
     * Keep the link between two properties symmetric: linking A to B also
     * links B to A, and unlinking one side releases the other.
     */
    private function applyLink(Property $property, ?int $linkedPropertyId, ?int $previous = null): void
    {
        $previous = $previous ?? $property->linked_property_id;

        if ($linkedPropertyId === null || $linkedPropertyId === $property->id) {
            if ($previous !== null) {
                Property::whereKey($previous)->where('linked_property_id', $property->id)->update(['linked_property_id' => null]);
            }

            $property->update(['linked_property_id' => null]);

            return;
        }

        if ($previous === $linkedPropertyId) {
            return;
        }

        if ($previous !== null) {
            Property::whereKey($previous)->where('linked_property_id', $property->id)->update(['linked_property_id' => null]);
        }

        $partner = Property::find($linkedPropertyId);
        if (! $partner instanceof Property) {
            return;
        }

        if ($partner->linked_property_id !== null && $partner->linked_property_id !== $property->id) {
            Property::whereKey($partner->linked_property_id)->where('linked_property_id', $partner->id)->update(['linked_property_id' => null]);
        }

        $partner->update(['linked_property_id' => $property->id]);
        $property->update(['linked_property_id' => $partner->id]);
    }
}
