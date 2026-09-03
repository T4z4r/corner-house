@php
    $setting = $settings[$key] ?? null;
    $current = $setting->value ?? '';
    $useWide = $wide ?? false;
@endphp
<div class="{{ $useWide ? '' : 'mb-3' }}">
    <label class="form-label fw-semibold">{{ $label }}</label>
    <div class="dz-upload-area" id="dz-{{ $key }}" data-key="{{ $key }}">
        @if ($current)
            <div class="dz-preview-wrapper mb-2">
                <img src="{{ asset('storage/'.$current) }}" alt="{{ $label }}" class="rounded border dz-current-image" style="max-height: 80px;">
            </div>
        @endif
        <div class="dz-dropzone rounded border border-2 border-dashed p-3 text-center" style="cursor:pointer; min-height: 60px; transition: all 0.2s;">
            <i class="bi bi-cloud-arrow-up fs-4 text-muted"></i>
            <div class="small text-muted mt-1">Drop image here or click to browse</div>
            <div class="dz-filename small text-muted mt-1" style="display:none"></div>
        </div>
        <input type="hidden" name="{{ $key }}" value="{{ $current }}" class="dz-path-input">
        @if ($hint ?? null)
            <div class="form-text">{{ $hint }}</div>
        @endif
    </div>
</div>
