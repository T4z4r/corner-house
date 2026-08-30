@extends('layouts.admin.app')

@section('title', 'Gallery Settings')

@section('content')
    <div class="ch-page-header">
        <div>
            <div class="ch-breadcrumb">Settings / Gallery</div>
            <h4>Gallery Settings</h4>
            <p class="ch-subtitle">{{ $images->total() }} image{{ $images->total() === 1 ? '' : 's' }} in gallery</p>
        </div>
    </div>

    {{-- Dropzone upload area --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white">
            <h6 class="mb-0"><i class="bi bi-upload me-2"></i>Upload images</h6>
        </div>
        <div class="card-body">
            <div id="dz-gallery-upload">
                <div class="dz-dropzone rounded border border-2 border-dashed p-4 text-center" style="cursor:pointer; min-height: 80px; transition: all 0.2s;">
                    <i class="bi bi-cloud-arrow-up fs-1 text-muted"></i>
                    <p class="mt-2 mb-0">Drag & drop images here or click to browse</p>
                    <small class="text-muted">JPEG, PNG, WebP — max 5 MB each</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Image grid --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="bi bi-images me-2"></i>Gallery images</h6>
            <small class="text-muted">Drag to reorder. Changes save automatically.</small>
        </div>
        <div class="card-body">
            @forelse ($images as $image)
                <div class="d-flex align-items-start gap-3 p-3 mb-3 border rounded gallery-item position-relative"
                     data-id="{{ $image->id }}">
                    {{-- Drag handle --}}
                    <div class="text-muted fs-5 cursor-grab gallery-drag-handle" title="Drag to reorder">
                        <i class="bi bi-grip-vertical"></i>
                    </div>

                    {{-- Thumbnail --}}
                    <div class="flex-shrink-0" style="width:120px;height:80px;overflow:hidden;border-radius:6px;background:#f4efe6;">
                        <img src="{{ asset('storage/'.$image->path) }}" alt="{{ $image->alt }}" style="width:100%;height:100%;object-fit:cover;">
                    </div>

                    {{-- Details --}}
                    <div class="flex-grow-1">
                        <div class="mb-2">
                            <label class="form-label small text-muted mb-1">Alt text</label>
                            <input type="text" class="form-control form-control-sm gallery-alt-input"
                                   value="{{ $image->alt }}" placeholder="Describe this image">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small text-muted mb-1">Caption</label>
                            <input type="text" class="form-control form-control-sm gallery-caption-input"
                                   value="{{ $image->caption }}" placeholder="Optional caption shown below the image">
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input gallery-active-toggle" type="checkbox"
                                       data-id="{{ $image->id }}" {{ $image->is_active ? 'checked' : '' }}>
                                <label class="form-check-label small">Visible on website</label>
                            </div>
                            <button class="btn btn-sm btn-outline-primary gallery-save-btn" data-id="{{ $image->id }}">
                                <i class="bi bi-check-lg"></i> Save
                            </button>
                        </div>
                    </div>

                    {{-- Delete --}}
                    <div class="flex-shrink-0">
                        <form method="POST" action="{{ route('admin.gallery.destroy', $image) }}"
                              onsubmit="return confirm('Delete this image?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="text-center py-5" id="gallery-empty">
                    <i class="bi bi-images fs-1 text-muted"></i>
                    <p class="mt-2 text-muted">No gallery images yet. Upload some above to get started.</p>
                </div>
            @endforelse
        </div>
    </div>

    @if ($images->hasPages())
        <div class="mt-3">{{ $images->links() }}</div>
    @endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const uploadUrl = '{{ route("admin.gallery.upload") }}';
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    // --- Dropzone ---
    const dzArea = document.getElementById('dz-gallery-upload');
    if (dzArea) {
        const dropzoneEl = dzArea.querySelector('.dz-dropzone');

        const myDropzone = new Dropzone(dropzoneEl, {
            url: uploadUrl,
            method: 'post',
            acceptedFiles: 'image/jpeg,image/png,image/webp',
            maxFilesize: 5,
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            sending: function (file, xhr, formData) {
                formData.append('_token', csrfToken);
            },
            addedfile: function () {},
            success: function (file, response) {
                const empty = document.getElementById('gallery-empty');
                if (empty) empty.remove();

                const html = `
                    <div class="d-flex align-items-start gap-3 p-3 mb-3 border rounded gallery-item position-relative" data-id="${response.id}">
                        <div class="text-muted fs-5 cursor-grab gallery-drag-handle" title="Drag to reorder">
                            <i class="bi bi-grip-vertical"></i>
                        </div>
                        <div class="flex-shrink-0" style="width:120px;height:80px;overflow:hidden;border-radius:6px;background:#f4efe6;">
                            <img src="${response.url}" alt="${response.alt || ''}" style="width:100%;height:100%;object-fit:cover;">
                        </div>
                        <div class="flex-grow-1">
                            <div class="mb-2">
                                <label class="form-label small text-muted mb-1">Alt text</label>
                                <input type="text" class="form-control form-control-sm gallery-alt-input" value="${response.alt || ''}" placeholder="Describe this image">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small text-muted mb-1">Caption</label>
                                <input type="text" class="form-control form-control-sm gallery-caption-input" value="" placeholder="Optional caption shown below the image">
                            </div>
                            <div class="d-flex align-items-center gap-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input gallery-active-toggle" type="checkbox" data-id="${response.id}" checked>
                                    <label class="form-check-label small">Visible on website</label>
                                </div>
                                <button class="btn btn-sm btn-outline-primary gallery-save-btn" data-id="${response.id}">
                                    <i class="bi bi-check-lg"></i> Save
                                </button>
                            </div>
                        </div>
                        <div class="flex-shrink-0">
                            <form method="POST" action="/admin/gallery/${response.id}" onsubmit="return confirm('Delete this image?')">
                                <input type="hidden" name="_token" value="${csrfToken}">
                                <input type="hidden" name="_method" value="DELETE">
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>`;
                document.querySelector('.card-body').insertAdjacentHTML('beforeend', html);
                // Make the new item draggable
                const newItem = document.querySelector(`.gallery-item[data-id="${response.id}"]`);
                if (newItem) newItem.setAttribute('draggable', 'true');
                file.previewElement.remove();
            },
            error: function (file, errorMessage) {
                const msg = typeof errorMessage === 'string' ? errorMessage : (errorMessage.message || 'Upload failed');
                file.previewElement.classList.add('dz-error');
                const errSpan = document.createElement('div');
                errSpan.className = 'small text-danger mt-1';
                errSpan.textContent = msg;
                file.previewElement.appendChild(errSpan);
            },
        });
    }

    // --- Save individual image ---
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.gallery-save-btn');
        if (!btn) return;

        const id = btn.dataset.id;
        const item = btn.closest('.gallery-item');
        const altInput = item.querySelector('.gallery-alt-input');
        const captionInput = item.querySelector('.gallery-caption-input');
        const activeToggle = item.querySelector('.gallery-active-toggle');

        fetch(`/admin/gallery/${id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                alt: altInput.value,
                caption: captionInput.value,
                is_active: activeToggle.checked,
            }),
        })
        .then(r => r.json())
        .then(() => {
            btn.innerHTML = '<i class="bi bi-check-lg"></i> Saved';
            btn.classList.replace('btn-outline-primary', 'btn-success');
            setTimeout(() => {
                btn.innerHTML = '<i class="bi bi-check-lg"></i> Save';
                btn.classList.replace('btn-success', 'btn-outline-primary');
            }, 1500);
        });
    });

    // --- Drag to reorder ---
    let dragItem = null;
    let dragOverItem = null;

    function initDraggable() {
        document.querySelectorAll('.gallery-item').forEach(el => el.setAttribute('draggable', 'true'));
    }
    initDraggable();

    // Use dragstart on the handle only
    document.addEventListener('dragstart', function (e) {
        const handle = e.target.closest('.gallery-drag-handle');
        if (!handle) { e.preventDefault(); return; }
        dragItem = handle.closest('.gallery-item');
        if (!dragItem) return;
        dragItem.classList.add('is-dragging');
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', dragItem.dataset.id);
    });

    document.addEventListener('dragover', function (e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';

        const target = e.target.closest('.gallery-item');
        if (!target || target === dragItem) return;

        // Remove previous highlight
        if (dragOverItem && dragOverItem !== target) {
            dragOverItem.classList.remove('drag-over');
        }
        dragOverItem = target;
        target.classList.add('drag-over');
    });

    document.addEventListener('dragleave', function (e) {
        const target = e.target.closest('.gallery-item');
        if (target && target !== dragItem) {
            target.classList.remove('drag-over');
        }
    });

    document.addEventListener('drop', function (e) {
        e.preventDefault();
        const target = e.target.closest('.gallery-item');
        if (!target || target === dragItem || !dragItem) return;

        const rect = target.getBoundingClientRect();
        const midY = rect.top + rect.height / 2;

        if (e.clientY < midY) {
            target.parentNode.insertBefore(dragItem, target);
        } else {
            target.parentNode.insertBefore(dragItem, target.nextSibling);
        }

        cleanup();
        saveOrder();
    });

    document.addEventListener('dragend', function () {
        cleanup();
    });

    function cleanup() {
        if (dragOverItem) dragOverItem.classList.remove('drag-over');
        if (dragItem) dragItem.classList.remove('is-dragging');
        dragItem = null;
        dragOverItem = null;
    }

    function saveOrder() {
        const ids = [...document.querySelectorAll('.gallery-item')].map(el => el.dataset.id);
        fetch('/admin/gallery/reorder', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ ids }),
        });
    }
});
</script>
@endpush
