@extends('layouts.admin.app')

@section('title', 'Website settings')

@section('content')
    <div class="ch-page-header">
        <div>
            <div class="ch-breadcrumb"><a href="{{ route('admin.settings') }}">Settings</a> / Website</div>
            <h4>{{ $pageTitle }}</h4>
            <p class="ch-subtitle">{{ $pageSubtitle }}</p>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.settings.update') }}">
        @csrf
        @method('PUT')

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h6 class="mb-0">Branding and logos</h6>
                        <div class="small text-muted">The logos and small icons shown across your website.</div>
                    </div>
                    <div class="card-body">
                        @include('admin.settings.website.partials.upload-field', ['key' => 'website_logo', 'label' => 'Logo', 'hint' => 'The main logo shown in the top-left of every page.'])
                        @include('admin.settings.website.partials.upload-field', ['key' => 'website_footer_logo', 'label' => 'Footer logo', 'hint' => 'The logo shown at the bottom of the site, if you want a different version.'])
                        @include('admin.settings.website.partials.upload-field', ['key' => 'website_favicon', 'label' => 'Small icon (tab icon)', 'hint' => 'The tiny icon shown next to your website name in the browser tab.'])
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h6 class="mb-0">Homepage intro</h6>
                        <div class="small text-muted">The headline and opening message on the front page.</div>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            @php $s = $settings['website_hero_headline'] ?? null; @endphp
                            <label class="form-label fw-semibold" for="website_hero_headline">Big headline</label>
                            <input type="text" id="website_hero_headline" name="website_hero_headline" class="form-control" value="{{ $s->value ?? '' }}" placeholder="Welcome to Corner House">
                            <div class="form-text">The large title at the top of the homepage.</div>
                        </div>
                        <div class="mb-3">
                            @php $s = $settings['website_hero_subtitle'] ?? null; @endphp
                            <label class="form-label fw-semibold" for="website_hero_subtitle">Sub-heading</label>
                            <input type="text" id="website_hero_subtitle" name="website_hero_subtitle" class="form-control" value="{{ $s->value ?? '' }}" placeholder="Your perfect countryside escape awaits">
                            <div class="form-text">The line shown just under the headline.</div>
                        </div>
                        <div class="mb-3">
                            @php $s = $settings['website_tagline'] ?? null; @endphp
                            <label class="form-label fw-semibold" for="website_tagline">Tagline</label>
                            <input type="text" id="website_tagline" name="website_tagline" class="form-control" value="{{ $s->value ?? '' }}" placeholder="A luxury countryside retreat">
                            <div class="form-text">A short slogan used around the site.</div>
                        </div>
                        @include('admin.settings.website.partials.upload-field', ['key' => 'website_hero_image', 'label' => 'Main background photo', 'hint' => 'The large photo behind the headline on the homepage.', 'wide' => true])
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h6 class="mb-0">Homepage photo gallery</h6>
                        <div class="small text-muted">The two photos shown side-by-side after the headline.</div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                @include('admin.settings.website.partials.upload-field', ['key' => 'website_hero_gallery_main', 'label' => 'Large photo', 'hint' => 'The bigger image in the gallery.'])
                            </div>
                            <div class="col-md-6">
                                @include('admin.settings.website.partials.upload-field', ['key' => 'website_hero_gallery_small', 'label' => 'Small photo', 'hint' => 'The smaller image that sits beside the large one.'])
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h6 class="mb-0">About section</h6>
                        <div class="small text-muted">The introduction about the house on the homepage.</div>
                    </div>
                    <div class="card-body">
                        @php $s = $settings['website_about_text'] ?? null; @endphp
                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="website_about_text">About text</label>
                            <textarea id="website_about_text" name="website_about_text" class="form-control" rows="4">{{ $s->value ?? '' }}</textarea>
                            <div class="form-text">A short paragraph introducing Corner House.</div>
                        </div>
                        @include('admin.settings.website.partials.upload-field', ['key' => 'website_about_image', 'label' => 'House exterior photo', 'hint' => 'The photo shown next to the about text.', 'wide' => true])
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h6 class="mb-0">Contact details</h6>
                        <div class="small text-muted">How guests can reach you.</div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                @php $s = $settings['website_contact_email'] ?? null; @endphp
                                <label class="form-label fw-semibold" for="website_contact_email">Email</label>
                                <input type="email" id="website_contact_email" name="website_contact_email" class="form-control" value="{{ $s->value ?? '' }}" placeholder="hello@example.com">
                            </div>
                            <div class="col-md-6">
                                @php $s = $settings['website_contact_phone'] ?? null; @endphp
                                <label class="form-label fw-semibold" for="website_contact_phone">Phone</label>
                                <input type="tel" id="website_contact_phone" name="website_contact_phone" class="form-control" value="{{ $s->value ?? '' }}" placeholder="+44 ...">
                            </div>
                            <div class="col-12">
                                @php $s = $settings['website_address'] ?? null; @endphp
                                <label class="form-label fw-semibold" for="website_address">Address</label>
                                <input type="text" id="website_address" name="website_address" class="form-control" value="{{ $s->value ?? '' }}" placeholder="House name, road, town, postcode">
                                <div class="form-text">Shown on the contact page.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h6 class="mb-0">Social media</h6>
                        <div class="small text-muted">Links to your profiles. Leave blank to hide.</div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                @php $s = $settings['website_facebook'] ?? null; @endphp
                                <label class="form-label fw-semibold" for="website_facebook">Facebook</label>
                                <input type="url" id="website_facebook" name="website_facebook" class="form-control" value="{{ $s->value ?? '' }}" placeholder="https://facebook.com/...">
                            </div>
                            <div class="col-md-6">
                                @php $s = $settings['website_instagram'] ?? null; @endphp
                                <label class="form-label fw-semibold" for="website_instagram">Instagram</label>
                                <input type="url" id="website_instagram" name="website_instagram" class="form-control" value="{{ $s->value ?? '' }}" placeholder="https://instagram.com/...">
                            </div>
                            <div class="col-md-6">
                                @php $s = $settings['website_twitter'] ?? null; @endphp
                                <label class="form-label fw-semibold" for="website_twitter">X (Twitter)</label>
                                <input type="url" id="website_twitter" name="website_twitter" class="form-control" value="{{ $s->value ?? '' }}" placeholder="https://x.com/...">
                            </div>
                            <div class="col-md-6">
                                @php $s = $settings['website_youtube'] ?? null; @endphp
                                <label class="form-label fw-semibold" for="website_youtube">YouTube</label>
                                <input type="url" id="website_youtube" name="website_youtube" class="form-control" value="{{ $s->value ?? '' }}" placeholder="https://youtube.com/...">
                            </div>
                            <div class="col-md-6">
                                @php $s = $settings['website_tiktok'] ?? null; @endphp
                                <label class="form-label fw-semibold" for="website_tiktok">TikTok</label>
                                <input type="url" id="website_tiktok" name="website_tiktok" class="form-control" value="{{ $s->value ?? '' }}" placeholder="https://tiktok.com/...">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h6 class="mb-0">Footer</h6>
                        <div class="small text-muted">The text and details at the very bottom of the site.</div>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            @php $s = $settings['website_footer_text'] ?? null; @endphp
                            <label class="form-label fw-semibold" for="website_footer_text">Footer text</label>
                            <textarea id="website_footer_text" name="website_footer_text" class="form-control" rows="2">{{ $s->value ?? '' }}</textarea>
                            <div class="form-text">A short line or copyright notice shown in the footer.</div>
                        </div>
                        <div class="mb-3">
                            @php $s = $settings['website_footer_capacity'] ?? null; @endphp
                            <label class="form-label fw-semibold" for="website_footer_capacity">Capacity line</label>
                            <input type="text" id="website_footer_capacity" name="website_footer_capacity" class="form-control" value="{{ $s->value ?? '' }}">
                            <div class="form-text">A one-line summary of how many people the house sleeps.</div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h6 class="mb-0">Ratings and reviews</h6>
                        <div class="small text-muted">The numbers shown on the homepage.</div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                @php $s = $settings['review_score'] ?? null; @endphp
                                <label class="form-label fw-semibold" for="review_score">Average rating</label>
                                <input type="text" id="review_score" name="review_score" class="form-control" value="{{ $s->value ?? '' }}" placeholder="4.95">
                                <div class="form-text">Out of 5, e.g. 4.95.</div>
                            </div>
                            <div class="col-md-6">
                                @php $s = $settings['review_count'] ?? null; @endphp
                                <label class="form-label fw-semibold" for="review_count">Number of reviews</label>
                                <input type="number" id="review_count" name="review_count" class="form-control" value="{{ $s->value ?? '' }}" min="0">
                                <div class="form-text">How many guest reviews are shown.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h6 class="mb-0">Booking platform links</h6>
                        <div class="small text-muted">Where guests can book you on other sites. Leave blank to hide.</div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                @php $s = $settings['platform_airbnb_url'] ?? null; @endphp
                                <label class="form-label fw-semibold" for="platform_airbnb_url">Airbnb</label>
                                <input type="url" id="platform_airbnb_url" name="platform_airbnb_url" class="form-control" value="{{ $s->value ?? '' }}" placeholder="https://airbnb.com/...">
                            </div>
                            <div class="col-md-6">
                                @php $s = $settings['platform_booking_url'] ?? null; @endphp
                                <label class="form-label fw-semibold" for="platform_booking_url">Booking.com</label>
                                <input type="url" id="platform_booking_url" name="platform_booking_url" class="form-control" value="{{ $s->value ?? '' }}" placeholder="https://booking.com/...">
                            </div>
                            <div class="col-md-6">
                                @php $s = $settings['platform_vrbo_url'] ?? null; @endphp
                                <label class="form-label fw-semibold" for="platform_vrbo_url">VRBO</label>
                                <input type="url" id="platform_vrbo_url" name="platform_vrbo_url" class="form-control" value="{{ $s->value ?? '' }}" placeholder="https://vrbo.com/...">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h6 class="mb-0">Video and sharing</h6>
                        <div class="small text-muted">A tour video and the image used when your site is shared.</div>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            @php $s = $settings['website_video_url'] ?? null; @endphp
                            <label class="form-label fw-semibold" for="website_video_url">Video tour link</label>
                            <input type="url" id="website_video_url" name="website_video_url" class="form-control" value="{{ $s->value ?? '' }}" placeholder="https://youtube.com/...">
                            <div class="form-text">A YouTube, Vimeo or MP4 link to your tour video.</div>
                        </div>
                        @include('admin.settings.website.partials.upload-field', ['key' => 'website_og_image', 'label' => 'Preview image when shared', 'hint' => 'The image shown when someone shares your website on social media or messaging apps.', 'wide' => true])
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h6 class="mb-0">Serengeti Spirits</h6>
                        <div class="small text-muted">Your on-site distillery link and logo.</div>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            @php $s = $settings['spirits_website'] ?? null; @endphp
                            <label class="form-label fw-semibold" for="spirits_website">Serengeti Spirits website</label>
                            <input type="url" id="spirits_website" name="spirits_website" class="form-control" value="{{ $s->value ?? '' }}" placeholder="https://www.serengetispirits.com">
                        </div>
                        @include('admin.settings.website.partials.upload-field', ['key' => 'website_spirits_logo', 'label' => 'Serengeti Spirits logo', 'hint' => 'The logo shown for the Spirits section.', 'wide' => true])
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm sticky-top" style="top: 1rem;">
                    <div class="card-header bg-white"><h6 class="mb-0">Need help?</h6></div>
                    <div class="card-body">
                        <p class="small text-muted mb-1">This page controls everything a visitor sees on your public website. Changes save instantly when you click <strong>Save changes</strong> below.</p>
                        <p class="small text-muted mb-0">Anything you leave blank is simply hidden from the site.</p>
                    </div>
                    <div class="card-footer bg-white">
                        <button type="submit" class="btn btn-ch-primary w-100"><i class="bi bi-check-lg me-1"></i>Save changes</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
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
