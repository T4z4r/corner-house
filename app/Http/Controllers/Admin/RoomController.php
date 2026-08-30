<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\Room;
use App\Models\RoomImage;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RoomController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function index(Property $property): View
    {
        $rooms = $property->rooms()->withCount('images')->orderBy('name')->get();

        return view('admin.rooms.index', ['property' => $property, 'rooms' => $rooms]);
    }

    public function show(Room $room): View
    {
        return view('admin.rooms.show', [
            'room' => $room->load(['property', 'images']),
        ]);
    }

    public function manage(Request $request): View
    {
        $query = Room::query()
            ->with(['property:id,name,status', 'images'])
            ->withCount('images');

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request): void {
                $q->where('name', 'like', '%'.$request->string('search').'%')
                    ->orWhere('type', 'like', '%'.$request->string('search').'%');
            });
        }

        $query->when($request->filled('status'), function ($q) use ($request): void {
            $q->where('status', $request->string('status'));
        });

        $rooms = $query->orderBy('property_id')->orderBy('name')->get();

        $properties = Property::query()->orderBy('name')->get(['id', 'name']);

        return view('admin.rooms.manage', [
            'rooms' => $rooms,
            'properties' => $properties,
        ]);
    }

    public function create(Request $request, Property $property): View
    {
        return view('admin.rooms.create', [
            'property' => $property,
            'pageAction' => 'create',
        ]);
    }

    public function store(Request $request, Property $property): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = $data['slug'] ?? Str::slug($data['name'].'-'.$property->id);

        $room = $property->rooms()->create($data);

        if ($request->hasFile('images')) {
            $this->storeImages($request, $room);
        }

        $this->auditLogger->log('rooms.created', 'rooms', 'room', (string) $room->id, newValues: $data);

        return redirect()->route('admin.rooms.edit', $room)->with('status', 'Room created.');
    }

    public function edit(Room $room): View
    {
        return view('admin.rooms.edit', [
            'room' => $room->load(['property', 'images']),
        ]);
    }

    public function update(Request $request, Room $room): RedirectResponse
    {
        $old = $room->only(['name', 'status', 'base_rate', 'capacity', 'min_stay']);

        $data = $this->validated($request);
        $room->update($data);

        if ($request->hasFile('images')) {
            $this->storeImages($request, $room);
        }

        $new = $room->only(['name', 'status', 'base_rate', 'capacity', 'min_stay']);
        $this->auditLogger->log('rooms.updated', 'rooms', 'room', (string) $room->id, $old, $new);

        return redirect()->route('admin.rooms.edit', $room)->with('status', 'Room updated.');
    }

    public function destroy(Room $room): RedirectResponse
    {
        $propertyId = $room->property_id;
        $this->auditLogger->log('rooms.deleted', 'rooms', 'room', (string) $room->id, newValues: ['name' => $room->name]);
        $room->delete();

        return redirect()->route('admin.rooms.index', $propertyId)->with('status', 'Room deleted.');
    }

    public function destroyImage(RoomImage $image): RedirectResponse
    {
        Storage::disk('public')->delete($image->path);
        $image->delete();

        return redirect()->route('admin.rooms.edit', $image->room_id)->with('status', 'Image removed.');
    }

    public function uploadImage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'image', 'max:5120'],
            'room_id' => ['required', 'exists:rooms,id'],
        ]);

        $room = Room::findOrFail($validated['room_id']);
        $sort = $room->images()->count();

        $path = $request->file('file')->store('room-images', 'public');

        $image = RoomImage::create([
            'room_id' => $room->id,
            'path' => $path,
            'alt' => $room->name,
            'sort_order' => $sort,
            'is_primary' => $sort === 0,
        ]);

        return response()->json([
            'ok' => true,
            'path' => $path,
            'image_id' => $image->id,
            'is_primary' => $image->is_primary,
        ]);
    }

    public function destroyUploadedImage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'image_id' => ['required', 'exists:room_images,id'],
        ]);

        $image = RoomImage::findOrFail($validated['image_id']);
        Storage::disk('public')->delete($image->path);
        $image->delete();

        return response()->json(['ok' => true]);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['nullable', 'string', 'max:100'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'sleeps' => ['nullable', 'integer', 'min:1'],
            'bedrooms' => ['nullable', 'integer', 'min:0'],
            'bathrooms' => ['nullable', 'integer', 'min:0'],
            'is_private' => ['nullable', 'boolean'],
            'status' => ['required', 'in:active,inactive,maintenance'],
            'base_rate' => ['nullable', 'numeric', 'min:0'],
            'min_stay' => ['nullable', 'integer', 'min:1'],
            'max_stay' => ['nullable', 'integer', 'gt:min_stay'],
        ]);
    }

    private function storeImages(Request $request, Room $room): void
    {
        $sort = $room->images()->count();

        foreach ($request->file('images') as $image) {
            $path = $image->store('room-images', 'public');

            RoomImage::create([
                'room_id' => $room->id,
                'path' => $path,
                'alt' => $request->input('image_alts')[$image->getClientOriginalName()] ?? $room->name,
                'sort_order' => $sort++,
                'is_primary' => $sort === 1,
            ]);
        }
    }
}
