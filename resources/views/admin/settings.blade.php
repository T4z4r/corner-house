@extends('layouts.admin.app')

@section('title', 'Settings')

@section('content')
    <div class="ch-page-header">
        <div>
            <div class="ch-breadcrumb">System / Settings</div>
            <h4>{{ $pageTitle ?? 'Settings' }}</h4>
            <p class="ch-subtitle">{{ $pageSubtitle ?? 'Configure platform-wide options' }}</p>
        </div>
        @if (empty($singleGroup))
            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#newSettingModal">
                <i class="bi bi-plus-lg me-1"></i>New setting
            </button>
        @endif
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            @if (empty($singleGroup))
                <ul class="nav nav-tabs mb-3" role="tablist">
                    @foreach ($grouped as $group => $settings)
                        <li class="nav-item" role="presentation">
                            <button class="nav-link {{ $loop->first ? 'active' : '' }}"
                                    data-bs-toggle="tab"
                                    data-bs-target="#tab-{{ $loop->iteration }}"
                                    type="button"
                                    role="tab">{{ ucfirst($group) }}</button>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                    <a href="{{ route('admin.settings') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left me-1"></i>All settings
                    </a>
                    <div class="small text-muted">Only the selected settings group is shown on this page.</div>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.settings.update') }}">
                @csrf
                @method('PUT')

                <div class="tab-content">
                    @foreach ($grouped as $group => $settings)
                        <div class="tab-pane fade {{ empty($singleGroup) ? ($loop->first ? 'show active' : '') : 'show active' }}"
                             id="tab-{{ $loop->iteration }}"
                             role="tabpanel">
                            <div class="row g-3">
                                @foreach ($settings as $setting)
                                    <div class="{{ $setting->key === 'ai_instructions' ? 'col-12' : 'col-md-6' }}">
                                        <label class="form-label" for="{{ $setting->key }}">{{ $setting->label ?? $setting->key }}</label>
                                        @if ($setting->cast === 'boolean')
                                            <input type="hidden" name="{{ $setting->key }}" value="0">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox"
                                                       id="{{ $setting->key }}" name="{{ $setting->key }}"
                                                       value="1" @checked($setting->castValue())>
                                                <label class="form-check-label small" for="{{ $setting->key }}">Enabled</label>
                                            </div>
                                        @elseif ($setting->isSecret())
                                            <input type="password"
                                                   class="form-control"
                                                   id="{{ $setting->key }}"
                                                   name="{{ $setting->key }}"
                                                   value=""
                                                   autocomplete="new-password"
                                                   placeholder="{{ $setting->hasStoredSecret() ? 'Saved — leave blank to keep' : 'Paste API key' }}">
                                            @if ($setting->hasStoredSecret())
                                                <div class="form-text">A key is saved. Enter a new value only if you want to replace it.</div>
                                            @endif
                                        @elseif ($setting->key === 'ai_provider')
                                            <select class="form-select" id="{{ $setting->key }}" name="{{ $setting->key }}">
                                                <option value="openai" @selected($setting->value === 'openai')>OpenAI</option>
                                                <option value="claude" @selected($setting->value === 'claude')>Claude</option>
                                            </select>
                                        @elseif ($setting->key === 'mail_mailer')
                                            <select class="form-select" id="{{ $setting->key }}" name="{{ $setting->key }}">
                                                <option value="smtp" @selected($setting->value === 'smtp')>SMTP</option>
                                                <option value="sendmail" @selected($setting->value === 'sendmail')>Sendmail</option>
                                                <option value="log" @selected($setting->value === 'log')>Log</option>
                                                <option value="array" @selected($setting->value === 'array')>Array</option>
                                                <option value="failover" @selected($setting->value === 'failover')>Failover</option>
                                            </select>
                                        @elseif ($setting->key === 'mail_encryption')
                                            <select class="form-select" id="{{ $setting->key }}" name="{{ $setting->key }}">
                                                <option value="" @selected($setting->value === '')>None</option>
                                                <option value="tls" @selected($setting->value === 'tls')>TLS</option>
                                                <option value="ssl" @selected($setting->value === 'ssl')>SSL</option>
                                            </select>
                                        @elseif ($setting->key === 'mail_port')
                                            <input type="number"
                                                   class="form-control"
                                                   id="{{ $setting->key }}"
                                                   name="{{ $setting->key }}"
                                                   min="1"
                                                   max="65535"
                                                   value="{{ $setting->value }}">
                                        @elseif ($setting->key === 'mail_from_address')
                                            <input type="email"
                                                   class="form-control"
                                                   id="{{ $setting->key }}"
                                                   name="{{ $setting->key }}"
                                                   value="{{ $setting->value }}">
                                        @elseif ($setting->key === 'ai_instructions')
                                            <textarea class="form-control" id="{{ $setting->key }}" name="{{ $setting->key }}" rows="4">{{ $setting->value }}</textarea>
                                        @elseif (in_array($setting->key, ['website_footer_text', 'website_about_text']))
                                            <textarea class="form-control" id="{{ $setting->key }}" name="{{ $setting->key }}" rows="3">{{ $setting->value }}</textarea>
                                        @elseif (str_starts_with($setting->key, 'website_') && in_array($setting->key, ['website_facebook', 'website_instagram', 'website_twitter', 'website_youtube', 'website_tiktok']))
                                            <input type="url"
                                                   class="form-control"
                                                   id="{{ $setting->key }}"
                                                   name="{{ $setting->key }}"
                                                   value="{{ $setting->value }}"
                                                   placeholder="https://...">
                                        @elseif (str_starts_with($setting->key, 'platform_'))
                                            <input type="url"
                                                   class="form-control"
                                                   id="{{ $setting->key }}"
                                                   name="{{ $setting->key }}"
                                                   value="{{ $setting->value }}"
                                                   placeholder="https://...">
                                        @elseif ($setting->key === 'website_contact_email')
                                            <input type="email"
                                                   class="form-control"
                                                   id="{{ $setting->key }}"
                                                   name="{{ $setting->key }}"
                                                   value="{{ $setting->value }}"
                                                   placeholder="hello@example.com">
                                        @elseif ($setting->key === 'website_contact_phone')
                                            <input type="tel"
                                                   class="form-control"
                                                   id="{{ $setting->key }}"
                                                   name="{{ $setting->key }}"
                                                   value="{{ $setting->value }}"
                                                   placeholder="+44 ...">
                                        @elseif (in_array($setting->key, ['website_logo', 'website_footer_logo', 'website_favicon', 'website_hero_image', 'website_hero_gallery_main', 'website_hero_gallery_small', 'website_about_image', 'website_og_image', 'website_spirits_logo']))
                                            <div class="dz-upload-area" id="dz-{{ $setting->key }}" data-key="{{ $setting->key }}">
                                                @if ($setting->value)
                                                    <div class="dz-preview-wrapper mb-2">
                                                        <img src="{{ asset('storage/'.$setting->value) }}" alt="{{ $setting->label }}" class="rounded border dz-current-image" style="max-height: 80px;">
                                                    </div>
                                                @endif
                                                <div class="dz-dropzone rounded border border-2 border-dashed p-3 text-center" style="cursor:pointer; min-height: 60px; transition: all 0.2s;">
                                                    <i class="bi bi-cloud-arrow-up fs-4 text-muted"></i>
                                                    <div class="small text-muted mt-1">Drop image here or click to browse</div>
                                                    <div class="dz-filename small text-muted mt-1" style="display:none"></div>
                                                </div>
                                                <input type="hidden" name="{{ $setting->key }}" value="{{ $setting->value }}" class="dz-path-input">
                                            </div>
                                        @else
                                            <input type="text"
                                                   class="form-control"
                                                   id="{{ $setting->key }}"
                                                   name="{{ $setting->key }}"
                                                   value="{{ $setting->value }}">
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                <button type="submit" class="btn btn-ch-primary mt-4">Save settings</button>
            </form>
        </div>
    </div>

    <div class="modal fade" id="newSettingModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="{{ route('admin.settings.store') }}" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">New setting</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Group</label>
                        <input type="text" name="group" class="form-control" required placeholder="general">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Key</label>
                        <input type="text" name="key" class="form-control" required placeholder="property_name">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Label</label>
                        <input type="text" name="label" class="form-control" required placeholder="Property name">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Value</label>
                        <input type="text" name="value" class="form-control" placeholder="Enter the value">
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Cast</label>
                        <select name="cast" class="form-select">
                            <option value="string">String</option>
                            <option value="boolean">Boolean</option>
                            <option value="integer">Integer</option>
                            <option value="decimal">Decimal</option>
                            <option value="json">JSON</option>
                            <option value="secret">Secret</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-ch-primary">Create</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const uploadUrl = '{{ route("admin.settings.upload-image") }}';
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    document.querySelectorAll('.dz-upload-area').forEach(function (area) {
        const key = area.dataset.key;
        const dropzoneEl = area.querySelector('.dz-dropzone');
        const hiddenInput = area.querySelector('.dz-path-input');
        const filenameEl = area.querySelector('.dz-filename');

        const myDropzone = new Dropzone(dropzoneEl, {
            url: uploadUrl,
            method: 'post',
            acceptedFiles: 'image/*',
            maxFilesize: 5,
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            sending: function (file, xhr, formData) {
                formData.append('key', key);
                formData.append('_token', csrfToken);
            },
            success: function (file, response) {
                if (response.ok) {
                    hiddenInput.value = response.path;
                    filenameEl.textContent = file.name;
                    filenameEl.style.display = '';

                    const previewWrapper = area.querySelector('.dz-preview-wrapper');
                    if (!previewWrapper) {
                        const wrapper = document.createElement('div');
                        wrapper.className = 'dz-preview-wrapper mb-2';
                        wrapper.innerHTML = '<img src="' + URL.createObjectURL(file) + '" alt="Preview" class="rounded border dz-current-image" style="max-height: 80px;">';
                        area.insertBefore(wrapper, dropzoneEl);
                    } else {
                        const img = previewWrapper.querySelector('img');
                        if (img) img.src = URL.createObjectURL(file);
                    }

                    dropzoneEl.style.display = 'none';
                }
            },
            addedfile: function (file) {
                if (filenameEl) {
                    filenameEl.textContent = file.name;
                    filenameEl.style.display = '';
                }
            },
            error: function (file, errorMessage) {
                if (typeof errorMessage === 'string') {
                    alert(errorMessage);
                } else if (errorMessage.message) {
                    alert(errorMessage.message);
                }
                myDropzone.removeFile(file);
            },
        });

        dropzoneEl.addEventListener('click', function () {
            if (myDropzone.files.length === 0) {
                myDropzone.hiddenFileInput.click();
            }
        });

        if (hiddenInput.value) {
            dropzoneEl.style.display = 'none';
        }
    });
});
</script>
@endpush
