{{--
    Custom page loader — skeleton placeholders shown in the content section
    while the page paints, then faded out to reveal the real content.

    Rendered automatically by layouts/admin/app.blade.php when the page does
    not opt out via: @section('pageLoader', false)
--}}
<div class="ch-skeleton-layer" id="pageLoader" aria-hidden="true">
    <div class="ch-page-header">
        <div class="col-12">
            <div class="ch-skeleton ch-skeleton-text mb-2" style="width:120px"></div>
            <div class="ch-skeleton ch-skeleton-title" style="width:180px"></div>
            <div class="ch-skeleton ch-skeleton-text mt-2" style="width:240px"></div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        @for ($i = 0; $i < 8; $i++)
            <div class="col-6 col-md-3">
                <div class="ch-skeleton-card p-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="ch-skeleton ch-skeleton-icon"></div>
                        <div class="flex-grow-1">
                            <div class="ch-skeleton ch-skeleton-title mb-2" style="width:70%"></div>
                            <div class="ch-skeleton ch-skeleton-text" style="width:55%"></div>
                        </div>
                    </div>
                </div>
            </div>
        @endfor
    </div>

    <div class="ch-skeleton-card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <div class="ch-skeleton ch-skeleton-title" style="width:160px"></div>
            <div class="ch-skeleton ch-skeleton-chip"></div>
        </div>
        <div class="card-body p-0">
            @for ($i = 0; $i < 5; $i++)
                <div class="d-flex align-items-center gap-3 px-3 py-2 {{ $i > 0 ? 'border-top' : '' }}">
                    <div class="ch-skeleton ch-skeleton-avatar"></div>
                    <div class="flex-grow-1">
                        <div class="ch-skeleton ch-skeleton-text mb-2" style="width:40%"></div>
                        <div class="ch-skeleton ch-skeleton-text" style="width:65%"></div>
                    </div>
                    <div class="ch-skeleton ch-skeleton-chip d-none d-md-block"></div>
                </div>
            @endfor
        </div>
    </div>
</div>
