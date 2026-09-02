@extends('layouts.admin.app')

@section('title', $property->name)

@section('content')
    <div class="ch-page-header">
        <div>
            <div class="ch-breadcrumb"><a href="{{ route('admin.properties.index') }}">Properties</a> / {{ $property->name }}</div>
            <h4>{{ $property->name }}</h4>
            <p class="ch-subtitle">{{ $property->city }}, {{ $property->country }}</p>
        </div>
        <div class="d-flex gap-2">
            @can('rooms.view')
                <a href="{{ route('admin.rooms.index', $property) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-door-open me-1"></i>Rooms
                </a>
            @endcan
            @can('properties.update')
                <a href="{{ route('admin.properties.edit', $property) }}" class="btn btn-ch-primary">
                    <i class="bi bi-pencil me-1"></i>Edit
                </a>
            @endcan
            @can('properties.delete')
                <form method="POST" action="{{ route('admin.properties.destroy', $property) }}"
                      onsubmit="return confirm('Delete "{{ $property->name }}" and all its rooms, images, and policies? This cannot be undone.');">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-outline-danger"><i class="bi bi-trash me-1"></i>Delete</button>
                </form>
            @endcan
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            @can('properties.update')
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="bi bi-images me-2"></i>Images</h6>
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
            @endcan

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent border-bottom">
                    <h6 class="mb-0">Details</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="ch-label">Status</div>
                            <span class="ch-badge ch-badge-{{ $property->status === 'active' ? 'success' : ($property->status === 'maintenance' ? 'warning' : 'muted') }}">
                                <span class="dot"></span>{{ ucfirst($property->status) }}
                            </span>
                        </div>
                        <div class="col-md-4">
                            <div class="ch-label">Capacity</div>
                            <div class="fw-semibold">{{ $property->capacity ?? '-' }} guests</div>
                        </div>
                        <div class="col-md-4">
                            <div class="ch-label">Rooms</div>
                            <div class="fw-semibold">{{ $property->rooms->count() }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="ch-label">Bedrooms</div>
                            <div class="fw-semibold">{{ $property->bedrooms ?? '-' }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="ch-label">Bathrooms</div>
                            <div class="fw-semibold">{{ $property->bathrooms ?? '-' }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="ch-label">Postcode</div>
                            <div class="fw-semibold">{{ $property->postcode ?? '-' }}</div>
                        </div>
                        <div class="col-12">
                            <div class="ch-label">Address</div>
                            <div class="fw-semibold">
                                {{ $property->address_line_1 }}
                                @if ($property->address_line_2)
                                    , {{ $property->address_line_2 }}
                                @endif
                            </div>
                        </div>
                        @if ($property->description)
                            <div class="col-12">
                                <div class="ch-label">Description</div>
                                <div class="text-muted">{{ $property->description }}</div>
                            </div>
                        @endif
                        @if ($property->short_description)
                            <div class="col-12">
                                <div class="ch-label">Short Description</div>
                                <div class="text-muted">{{ $property->short_description }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            @if ($property->rooms->isNotEmpty())
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-transparent border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Rooms</h6>
                        @can('rooms.create')
                            <a href="{{ route('admin.rooms.create', $property) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-plus-lg me-1"></i>Add room
                            </a>
                        @endcan
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Name</th>
                                        <th>Type</th>
                                        <th>Capacity</th>
                                        <th>Base rate</th>
                                        <th>Status</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($property->rooms as $room)
                                        <tr>
                                            <td class="fw-semibold">
                                                <a href="{{ route('admin.rooms.show', $room) }}" class="text-decoration-none">{{ $room->name }}</a>
                                            </td>
                                            <td>{{ $room->type ?? '-' }}</td>
                                            <td>{{ $room->capacity ?? '-' }}</td>
                                            <td>&pound;{{ number_format($room->base_rate, 2) }}</td>
                                            <td>
                                                <span class="ch-badge ch-badge-{{ $room->status === 'active' ? 'success' : ($room->status === 'maintenance' ? 'warning' : 'muted') }}">
                                                    <span class="dot"></span>{{ ucfirst($room->status) }}
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                @can('rooms.update')
                                                    <a href="{{ route('admin.rooms.edit', $room) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                                @endcan
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-4">
            @if ($property->amenities->isNotEmpty())
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-transparent border-bottom">
                        <h6 class="mb-0">Amenities</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex flex-wrap gap-2">
                            @foreach ($property->amenities as $amenity)
                                <span class="ch-badge ch-badge-muted">
                                    @if ($amenity->icon)
                                        <i class="bi bi-{{ $amenity->icon }} me-1"></i>
                                    @endif
                                    {{ $amenity->name }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            @if ($property->policies->isNotEmpty())
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-transparent border-bottom">
                        <h6 class="mb-0">Policies</h6>
                    </div>
                    <div class="card-body">
                        @foreach ($property->policies as $policy)
                            <div class="mb-2">
                                <span class="fw-semibold">{{ $policy->title }}:</span>
                                <span class="text-muted">{{ $policy->description }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent border-bottom">
                    <h6 class="mb-0"><i class="bi bi-house-exclamation me-2"></i>House Rules</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="ch-label">Smoking</div>
                            <span class="ch-badge {{ $property->smoking_allowed ? 'ch-badge-success' : 'ch-badge-muted' }}">
                                <span class="dot"></span>{{ $property->smoking_allowed ? 'Allowed' : 'Not allowed' }}
                            </span>
                        </div>
                        <div class="col-6">
                            <div class="ch-label">Children</div>
                            <span class="ch-badge {{ $property->children_allowed ? 'ch-badge-success' : 'ch-badge-muted' }}">
                                <span class="dot"></span>{{ $property->children_allowed ? 'Allowed' : 'Not allowed' }}
                            </span>
                        </div>
                        <div class="col-6">
                            <div class="ch-label">Parties / Events</div>
                            <span class="ch-badge {{ $property->parties_allowed ? 'ch-badge-success' : 'ch-badge-muted' }}">
                                <span class="dot"></span>{{ $property->parties_allowed ? 'Allowed' : 'Not allowed' }}
                            </span>
                        </div>
                        <div class="col-6">
                            <div class="ch-label">Pets</div>
                            <span class="fw-semibold">{{ match($property->pets_allowed ?? 'no') { 'yes' => 'Yes', 'upon_request' => 'Upon request', default => 'No' } }}</span>
                        </div>
                        <div class="col-6">
                            <div class="ch-label">Check-in</div>
                            <div class="fw-semibold">{{ $property->check_in_from ?? '15:00' }} – {{ $property->check_in_until ?? '18:00' }}</div>
                        </div>
                        <div class="col-6">
                            <div class="ch-label">Check-out</div>
                            <div class="fw-semibold">{{ $property->check_out_from ?? '08:00' }} – {{ $property->check_out_until ?? '11:00' }}</div>
                        </div>
                    </div>
                    @if ($property->custom_rules)
                        <hr class="my-3">
                        <div class="ch-label mb-2">Custom rules</div>
                        <div class="text-muted" style="white-space: pre-line;">{{ $property->custom_rules }}</div>
                    @endif
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-bottom">
                    <h6 class="mb-0">Metadata</h6>
                </div>
                <div class="card-body">
                    <div class="ch-label">Created</div>
                    <div class="text-muted mb-2">{{ $property->created_at->format('d M Y H:i') }}</div>
                    <div class="ch-label">Last updated</div>
                    <div class="text-muted">{{ $property->updated_at->format('d M Y H:i') }}</div>
                </div>
            </div>
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
                    const grid = dzArea.querySelector('.row') || createGrid(dzArea);

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
