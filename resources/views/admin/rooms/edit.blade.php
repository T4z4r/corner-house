@extends('layouts.admin.app')

@section('title', 'Edit Room')

@section('content')
    <div class="ch-page-header">
        <div>
            <div class="ch-breadcrumb"><a href="{{ route('admin.rooms.index', $room->property) }}">{{ $room->property->name }} / Rooms</a> / Edit</div>
            <h4>Edit Room</h4>
            <p class="ch-subtitle">{{ $room->name }}</p>
        </div>
        @can('rooms.delete')
            <form method="POST" action="{{ route('admin.rooms.destroy', $room) }}"
                  onsubmit="return confirm('Delete this room?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash me-1"></i>Delete</button>
            </form>
        @endcan
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="ch-card-header"><h6><i class="bi bi-door-open"></i>Room details</h6></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.rooms.update', $room) }}">
                        @method('PUT')
                        @include('admin.rooms._form')
                        <div class="ch-form-actions">
                            <button type="submit" class="btn btn-ch-primary"><i class="bi bi-check-lg me-1"></i>Save changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="ch-card-header"><h6><i class="bi bi-images"></i>Images</h6></div>
                <div class="card-body">
                    @forelse ($room->images as $image)
                        <div class="mb-3 position-relative" id="sidebar-img-{{ $image->id }}">
                            <img src="{{ asset('storage/'.$image->path) }}" class="img-fluid rounded" alt="{{ $image->alt }}">
                            @if ($image->is_primary)
                                <span class="badge text-bg-primary position-absolute top-0 start-0 m-2">Primary</span>
                            @endif
                            <form method="POST" action="{{ route('admin.rooms.image.destroy', $image) }}" class="position-absolute top-0 end-0 m-2">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-danger" title="Remove image"><i class="bi bi-x-lg"></i></button>
                            </form>
                        </div>
                    @empty
                        <div class="ch-empty py-4">
                            <i class="bi bi-image"></i>
                            <div class="lead">No images yet</div>
                            <div class="small">Upload photos using the form on the left.</div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const uploadUrl = '{{ route("admin.rooms.upload-image") }}';
    const deleteUrl = '{{ route("admin.rooms.delete-uploaded-image") }}';
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const roomId = {{ $room->id }};

    const dzArea = document.getElementById('dz-room-images');
    if (!dzArea) return;

    const dropzoneEl = dzArea.querySelector('.dz-dropzone');

    const myDropzone = new Dropzone(dropzoneEl, {
        url: uploadUrl,
        method: 'post',
        acceptedFiles: 'image/*',
        maxFilesize: 5,
        parallelUploads: 3,
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
        },
        sending: function (file, xhr, formData) {
            formData.append('room_id', roomId);
            formData.append('_token', csrfToken);
        },
        success: function (file, response) {
            if (response.ok) {
                file.previewElement.classList.add('dz-success');
                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'btn btn-sm btn-outline-danger dz-remove mt-1';
                removeBtn.textContent = 'Remove';
                removeBtn.addEventListener('click', function () {
                    fetch(deleteUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ image_id: response.image_id }),
                    }).then(r => r.json()).then(data => {
                        if (data.ok) myDropzone.removeFile(file);
                    });
                });
                file.previewElement.appendChild(removeBtn);

                const sidebar = document.getElementById('sidebar-empty');
                if (sidebar) sidebar.remove();
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

    document.querySelectorAll('.dz-delete-existing').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const imageId = this.dataset.imageId;
            if (!confirm('Remove this image?')) return;
            fetch(deleteUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ image_id: imageId }),
            }).then(r => r.json()).then(data => {
                if (data.ok) {
                    const el = document.getElementById('img-' + imageId);
                    if (el) el.remove();
                    const sidebarEl = document.getElementById('sidebar-img-' + imageId);
                    if (sidebarEl) sidebarEl.remove();
                }
            });
        });
    });
});
</script>
@endpush
