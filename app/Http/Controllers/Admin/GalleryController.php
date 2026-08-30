<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GalleryImage;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class GalleryController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function index(): View
    {
        $images = GalleryImage::query()->ordered()->paginate(30);

        return view('admin.gallery.index', ['images' => $images]);
    }

    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'image', 'max:5120'],
        ]);

        $file = $request->file('file');
        $path = $file->store('gallery', 'public');

        $image = GalleryImage::create([
            'path' => $path,
            'alt' => $file->getClientOriginalName(),
            'sort_order' => GalleryImage::max('sort_order') + 1,
        ]);

        $this->auditLogger->log('gallery.created', 'gallery', 'image', (string) $image->id, newValues: ['path' => $path]);

        return response()->json([
            'id' => $image->id,
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
            'alt' => $image->alt,
        ]);
    }

    public function update(Request $request, GalleryImage $image): RedirectResponse
    {
        $data = $request->validate([
            'alt' => ['nullable', 'string', 'max:255'],
            'caption' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $old = $image->only(['alt', 'caption', 'is_active']);
        $image->update($data);
        $new = $image->only(['alt', 'caption', 'is_active']);

        $this->auditLogger->log('gallery.updated', 'gallery', 'image', (string) $image->id, $old, $new);

        return back()->with('status', 'Image updated.');
    }

    public function destroy(GalleryImage $image): RedirectResponse
    {
        Storage::disk('public')->delete($image->path);

        $this->auditLogger->log('gallery.deleted', 'gallery', 'image', (string) $image->id, newValues: ['path' => $image->path]);

        $image->delete();

        return back()->with('status', 'Image deleted.');
    }

    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:gallery_images,id'],
        ]);

        foreach ($request->ids as $index => $id) {
            GalleryImage::where('id', $id)->update(['sort_order' => $index]);
        }

        return response()->json(['ok' => true]);
    }

    public function destroyUploaded(Request $request): JsonResponse
    {
        $request->validate([
            'image_id' => ['required', 'integer', 'exists:gallery_images,id'],
        ]);

        $image = GalleryImage::findOrFail($request->image_id);
        Storage::disk('public')->delete($image->path);

        $this->auditLogger->log('gallery.deleted', 'gallery', 'image', (string) $image->id, newValues: ['path' => $image->path]);

        $image->delete();

        return response()->json(['ok' => true]);
    }
}
