@php
    $dzId = $dzId ?? 'dz-single-upload';
    $dzValueId = $dzId.'-value';
    $dzPreviewId = $dzId.'-preview';
    $dzDropzoneId = $dzId.'-dropzone';
    $dzFileNameId = $dzId.'-filename';
    $dzRemoveId = $dzId.'-remove';
    $dzChangeId = $dzId.'-change';
    $dzActionsId = $dzId.'-actions';
    $currentImage = $currentImage ?? null;
    $itemName = $itemName ?? 'image';
    $uploadRoute = $uploadRoute ?? '';
    $deleteRoute = $deleteRoute ?? '';
@endphp

<div class="dz-single-upload" id="{{ $dzId }}">
    <input type="hidden" name="image" id="{{ $dzValueId }}" value="{{ $currentImage }}">

    <div id="{{ $dzPreviewId }}" class="mb-2 @if (! $currentImage) d-none @endif">
        <img src="{{ $currentImage ? asset('storage/'.$currentImage) : '' }}" alt="Image preview" class="rounded border dz-current-image" style="max-height: 120px;">
    </div>

    <div id="{{ $dzDropzoneId }}" class="dz-dropzone rounded border border-2 border-dashed p-4 text-center @if ($currentImage) d-none @endif" style="cursor:pointer; min-height: 90px; transition: all 0.2s;">
        <i class="bi bi-cloud-arrow-up fs-3 text-muted"></i>
        <div class="small text-muted mt-1">Drag & drop image here or click to browse</div>
        <div id="{{ $dzFileNameId }}" class="dz-filename small text-muted mt-1" style="display:none"></div>
        <small class="text-muted d-block mt-1">JPEG, PNG, WebP — max 5 MB</small>
    </div>

    <div class="d-flex gap-2 mt-2 @if (! $currentImage) d-none @endif" id="{{ $dzActionsId }}">
        <button type="button" class="btn btn-sm btn-outline-secondary" id="{{ $dzChangeId }}">Change image</button>
        <button type="button" class="btn btn-sm btn-outline-danger" id="{{ $dzRemoveId }}">Remove image</button>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    (function () {
        const uploadUrl = @json($uploadRoute);
        const deleteUrl = @json($deleteRoute);
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        const valueInput = document.getElementById({{ json_encode($dzValueId) }});
        const previewWrap = document.getElementById({{ json_encode($dzPreviewId) }});
        const previewImg = previewWrap ? previewWrap.querySelector('img') : null;
        const dropzoneEl = document.getElementById({{ json_encode($dzDropzoneId) }});
        const filenameEl = document.getElementById({{ json_encode($dzFileNameId) }});
        const actions = document.getElementById({{ json_encode($dzActionsId) }});
        const changeBtn = document.getElementById({{ json_encode($dzChangeId) }});
        const removeBtn = document.getElementById({{ json_encode($dzRemoveId) }});

        if (! dropzoneEl || ! dropzoneEl.__dzInit) {
            dropzoneEl.__dzInit = true;

            const myDropzone = new Dropzone(dropzoneEl, {
                url: uploadUrl,
                method: 'post',
                acceptedFiles: 'image/jpeg,image/png,image/webp',
                maxFilesize: 5,
                maxFiles: 1,
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                sending: function (file, xhr, formData) {
                    formData.append('_token', csrfToken);
                },
                addedfile: function (file) {
                    filenameEl.textContent = file.name;
                    filenameEl.style.display = '';
                },
                success: function (file, response) {
                    if (! response.ok) {
                        alert('Upload failed');
                        myDropzone.removeFile(file);
                        return;
                    }

                    valueInput.value = response.path;
                    previewImg.src = response.url;
                    previewWrap.classList.remove('d-none');
                    dropzoneEl.classList.add('d-none');
                    actions.classList.remove('d-none');
                    filenameEl.style.display = 'none';
                    myDropzone.removeFile(file);
                },
                error: function (file, errorMessage) {
                    const msg = typeof errorMessage === 'string' ? errorMessage : (errorMessage.message || 'Upload failed');
                    alert(msg);
                    myDropzone.removeFile(file);
                },
            });

            dropzoneEl.addEventListener('click', function () {
                if (myDropzone.files.length === 0) {
                    myDropzone.hiddenFileInput.click();
                }
            });
        }

        if (changeBtn) {
            changeBtn.addEventListener('click', function () {
                valueInput.value = '';
                dropzoneEl.classList.remove('d-none');
                actions.classList.add('d-none');
                previewWrap.classList.add('d-none');
                filenameEl.style.display = 'none';
            });
        }

        if (removeBtn) {
            removeBtn.addEventListener('click', function () {
                const currentPath = valueInput.value;
                if (! currentPath) return;

                if (deleteUrl) {
                    fetch(deleteUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ path: currentPath }),
                    }).catch(function () {});
                }

                valueInput.value = '';
                previewWrap.classList.add('d-none');
                previewImg.src = '';
                actions.classList.add('d-none');
                dropzoneEl.classList.remove('d-none');
            });
        }
    })();
});
</script>
@endpush