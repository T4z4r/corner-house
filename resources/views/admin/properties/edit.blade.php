@extends('layouts.admin.app')

@section('title', 'Edit '.$property->name)

@section('content')
    <div class="ch-page-header">
        <div>
            <div class="ch-breadcrumb"><a href="{{ route('admin.properties.index') }}">Properties</a> / <a href="{{ route('admin.properties.show', $property) }}">{{ $property->name }}</a></div>
            <h4>Edit Property</h4>
            <p class="ch-subtitle">{{ $property->name }}</p>
        </div>
        <div class="d-flex gap-2">
            @can('properties.delete')
                <form method="POST" action="{{ route('admin.properties.destroy', $property) }}"
                      onsubmit="return confirm('Delete this property and all its rooms?');">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash me-1"></i>Delete</button>
                </form>
            @endcan
        </div>
    </div>

    {{-- Images --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="bi bi-images me-2"></i>Property images</h6>
            <small class="text-muted">{{ $property->images->count() }} image{{ $property->images->count() === 1 ? '' : 's' }}</small>
        </div>
        <div class="card-body">
            <div id="dz-property-images">
                <div class="dz-dropzone rounded border border-2 border-dashed p-3 text-center" style="cursor:pointer; min-height: 60px; transition: all 0.2s;">
                    <i class="bi bi-cloud-arrow-up fs-4 text-muted"></i>
                    <p class="mt-1 mb-0 small">Drag & drop images here or click to browse</p>
                    <small class="text-muted">JPEG, PNG, WebP — max 5 MB each</small>
                </div>
            </div>
            @if ($property->images->isNotEmpty())
                <div class="row g-2 mt-2">
                    @foreach ($property->images as $image)
                        <div class="col-6 col-md-4 col-lg-3">
                            <div class="position-relative rounded overflow-hidden" style="aspect-ratio:4/3; background:#f4efe6;">
                                <img src="{{ asset('storage/'.$image->path) }}" alt="{{ $image->alt }}" style="width:100%;height:100%;object-fit:cover;">
                                @if ($image->is_primary)
                                    <span class="badge bg-primary position-absolute top-0 start-0 m-1" style="font-size:0.65rem;">Primary</span>
                                @endif
                                <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1 dz-delete-existing" data-image-id="{{ $image->id }}" title="Delete" style="padding:0.15rem 0.4rem; font-size:0.7rem;">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="card border-0 shadow-sm ch-form-card">
        <div class="ch-card-header d-flex justify-content-between align-items-center">
            <div>
                <h6><i class="bi bi-building-gear"></i>Property details</h6>
            </div>
            @can('rooms.view')
                <a href="{{ route('admin.rooms.index', $property) }}" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-door-open me-1"></i>Manage rooms
                </a>
            @endcan
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.properties.update', $property) }}">
                @method('PUT')
                @include('admin.properties._form')
                <div class="ch-form-actions">
                    <button class="btn btn-ch-primary"><i class="bi bi-check-lg me-1"></i>Save changes</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const uploadUrl = '{{ route("admin.properties.upload-image") }}';
    const deleteUrl = '{{ route("admin.properties.image.destroy", ":id") }}';
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const propertyId = {{ $property->id }};

    const dzArea = document.getElementById('dz-property-images');
    if (dzArea) {
        const dropzoneEl = dzArea.querySelector('.dz-dropzone');

        const myDropzone = new Dropzone(dropzoneEl, {
            url: uploadUrl,
            method: 'post',
            acceptedFiles: 'image/jpeg,image/png,image/webp',
            maxFilesize: 5,
            parallelUploads: 3,
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            sending: function (file, xhr, formData) {
                formData.append('property_id', propertyId);
                formData.append('_token', csrfToken);
            },
            success: function (file, response) {
                if (response.ok) {
                    file.previewElement.classList.add('dz-success');
                    const grid = createGrid(dzArea);

                    const col = document.createElement('div');
                    col.className = 'col-6 col-md-4 col-lg-3';
                    col.innerHTML = `
                        <div class="position-relative rounded overflow-hidden" style="aspect-ratio:4/3; background:#f4efe6;">
                            <img src="${response.url}" alt="" style="width:100%;height:100%;object-fit:cover;">
                            <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1 dz-delete-existing" data-image-id="${response.image_id}" title="Delete" style="padding:0.15rem 0.4rem; font-size:0.7rem;">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>`;
                    grid.appendChild(col);

                    bindDeleteBtn(col.querySelector('.dz-delete-existing'));
                    file.previewElement.remove();
                }
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

    function createGrid(container) {
        const existing = container.querySelector('.row');
        if (existing) return existing;
        const grid = document.createElement('div');
        grid.className = 'row g-2 mt-2';
        container.appendChild(grid);
        return grid;
    }

    function bindDeleteBtn(btn) {
        if (!btn) return;
        btn.addEventListener('click', function () {
            const imageId = this.dataset.imageId;
            if (!confirm('Delete this image?')) return;
            fetch(deleteUrl.replace(':id', imageId), {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
            }).then(r => r.json()).then(data => {
                if (data.ok) this.closest('.col-6, .col-md-4, .col-lg-3').remove();
            });
        });
    }

    document.querySelectorAll('.dz-delete-existing').forEach(bindDeleteBtn);
});
</script>
@endpush
