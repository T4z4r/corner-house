@extends('layouts.admin.app')

@section('title', 'Schedule Settings')

@section('content')
    <div class="ch-page-header">
        <div>
            <div class="ch-breadcrumb">System / Schedule Settings</div>
            <h4>Schedule Settings</h4>
            <p class="ch-subtitle">Configure how often Beds24 data is synced</p>
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.schedule-settings.update') }}">
                @csrf
                @method('PUT')

                <div class="row g-4">
                    {{-- Bookings Sync --}}
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header bg-white">
                                <h6 class="mb-0"><i class="bi bi-calendar-check me-2"></i>Booking Sync</h6>
                            </div>
                            <div class="card-body">
                                @foreach ($settings->filter(fn ($s) => str_contains($s->key, 'bookings')) as $setting)
                                    <div class="mb-3">
                                        @if ($setting->cast === 'boolean')
                                            <label class="form-label fw-semibold">{{ $setting->label }}</label>
                                            <input type="hidden" name="{{ $setting->key }}" value="0">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox"
                                                       id="{{ $setting->key }}" name="{{ $setting->key }}"
                                                       value="1" @checked($setting->castValue())>
                                                <label class="form-check-label small" for="{{ $setting->key }}">Enabled</label>
                                            </div>
                                        @else
                                            <label class="form-label fw-semibold">{{ $setting->label }}</label>
                                            <select class="form-select" id="{{ $setting->key }}" name="{{ $setting->key }}">
                                                @foreach ($frequencies as $value => $label)
                                                    <option value="{{ $value }}" @selected($setting->value === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- Messages Sync --}}
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header bg-white">
                                <h6 class="mb-0"><i class="bi bi-chat-dots me-2"></i>Message Sync</h6>
                            </div>
                            <div class="card-body">
                                @foreach ($settings->filter(fn ($s) => str_contains($s->key, 'messages')) as $setting)
                                    <div class="mb-3">
                                        @if ($setting->cast === 'boolean')
                                            <label class="form-label fw-semibold">{{ $setting->label }}</label>
                                            <input type="hidden" name="{{ $setting->key }}" value="0">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox"
                                                       id="{{ $setting->key }}" name="{{ $setting->key }}"
                                                       value="1" @checked($setting->castValue())>
                                                <label class="form-check-label small" for="{{ $setting->key }}">Enabled</label>
                                            </div>
                                        @else
                                            <label class="form-label fw-semibold">{{ $setting->label }}</label>
                                            <select class="form-select" id="{{ $setting->key }}" name="{{ $setting->key }}">
                                                @foreach ($frequencies as $value => $label)
                                                    <option value="{{ $value }}" @selected($setting->value === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- Rates Push --}}
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header bg-white">
                                <h6 class="mb-0"><i class="bi bi-currency-pound me-2"></i>Rate Push</h6>
                            </div>
                            <div class="card-body">
                                @foreach ($settings->filter(fn ($s) => str_contains($s->key, 'rates')) as $setting)
                                    <div class="mb-3">
                                        @if ($setting->cast === 'boolean')
                                            <label class="form-label fw-semibold">{{ $setting->label }}</label>
                                            <input type="hidden" name="{{ $setting->key }}" value="0">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox"
                                                       id="{{ $setting->key }}" name="{{ $setting->key }}"
                                                       value="1" @checked($setting->castValue())>
                                                <label class="form-check-label small" for="{{ $setting->key }}">Enabled</label>
                                            </div>
                                        @else
                                            <label class="form-label fw-semibold">{{ $setting->label }}</label>
                                            <select class="form-select" id="{{ $setting->key }}" name="{{ $setting->key }}">
                                                @foreach ($frequencies as $value => $label)
                                                    <option value="{{ $value }}" @selected($setting->value === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-ch-primary mt-4">Save schedule settings</button>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-white">
            <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>cPanel Cron Setup</h6>
        </div>
        <div class="card-body">
            <p class="text-muted mb-3">For the schedule to run, you must set up a cron job in cPanel or your hosting provider. Add this single cron entry:</p>
            <div class="bg-light rounded p-3 mb-3">
                <code>* * * * * cd /path/to/corner-house &amp;&amp; php artisan schedule:run &gt;&gt; /dev/null 2&gt;&amp;1</code>
            </div>
            <p class="text-muted mb-0">This runs every minute and Laravel's scheduler will determine which jobs to execute based on the settings above. See the <a href="#">cPanel Scheduling Guide</a> for detailed instructions.</p>
        </div>
    </div>
@endsection
