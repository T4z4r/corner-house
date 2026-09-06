@extends('layouts.admin.app')

@section('title', 'Events')

@section('content')
    <div class="ch-page-header">
        <div>
            <div class="ch-breadcrumb">Management / Events</div>
            <h4>Events & Holidays</h4>
            <p class="ch-subtitle">{{ $items->total() }} event{{ $items->total() === 1 ? '' : 's' }} in view</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @can('chatbot.manage')
                <form method="POST" action="{{ route('admin.events.ai.generate') }}" class="d-inline">
                    @csrf
                    <button class="btn btn-outline-success" onclick="return confirm('Generate local holidays and events near the property with AI?');">
                        <i class="bi bi-stars me-1"></i>Generate with AI
                    </button>
                </form>
                <a href="{{ route('admin.events.create') }}" class="btn btn-ch-primary"><i class="bi bi-plus-lg me-1"></i>Add Event</a>
            @endcan
        </div>
    </div>

    <div class="ch-toolbar mb-3">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-12 col-md-3">
                <div class="position-relative">
                    <i class="bi bi-search position-absolute top-50 translate-middle-y ms-3 text-muted"></i>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control ps-5" placeholder="Search events...">
                </div>
            </div>
            <div class="col-6 col-md-2">
                <select name="range" class="form-select">
                    <option value="upcoming" @selected($range === 'upcoming')>Upcoming</option>
                    <option value="past" @selected($range === 'past')>Past</option>
                    <option value="all" @selected($range === 'all')>All</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <select name="status" class="form-select">
                    <option value="">All statuses</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="disabled" @selected(request('status') === 'disabled')>Disabled</option>
                </select>
            </div>
            <div class="col-12 col-md-3 d-flex gap-2">
                <button class="btn btn-ch-primary"><i class="bi bi-funnel me-1"></i>Filter</button>
                @if (request('search') || request('status') || $range !== 'upcoming')
                    <a href="{{ route('admin.events.index') }}" class="btn btn-light">Clear</a>
                @endif
            </div>
        </form>
    </div>

    @if (! $aiConfigured)
        <div class="alert alert-warning py-2 small">
            <i class="bi bi-info-circle me-1"></i>No AI provider API key is set. "Generate with AI" will fall back to standard UK bank holidays until a provider is configured in Settings.
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Title</th>
                            <th>Dates</th>
                            <th>Status</th>
                            <th>Website</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $item)
                            <tr>
                                <td class="fw-semibold">
                                    {{ $item->title }}
                                    @if ($item->source === 'ai')
                                        <span class="badge text-bg-success ms-1">AI</span>
                                    @endif
                                </td>
                                <td class="small">
                                    @if ($item->starts_at && $item->ends_at && $item->starts_at->isSameDay($item->ends_at))
                                        {{ $item->starts_at->format('d M Y') }}
                                    @elseif ($item->starts_at && $item->ends_at)
                                        {{ $item->starts_at->format('d M Y') }} → {{ $item->ends_at->format('d M Y') }}
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @can('chatbot.manage')
                                        <form method="POST" action="{{ route('admin.events.toggle', $item) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm {{ $item->status === 'active' ? 'btn-outline-success' : 'btn-outline-secondary' }}" title="{{ $item->status === 'active' ? 'Disable' : 'Enable' }}">
                                                <i class="bi {{ $item->status === 'active' ? 'bi-toggle-on' : 'bi-toggle-off' }}"></i>
                                                <span class="d-none d-md-inline ms-1">{{ $item->status === 'active' ? 'Active' : 'Disabled' }}</span>
                                            </button>
                                        </form>
                                    @else
                                        <span class="ch-badge ch-badge-{{ $item->status === 'active' ? 'success' : 'muted' }}">
                                            <span class="dot"></span>{{ ucfirst($item->status) }}
                                        </span>
                                    @endcan
                                </td>
                                <td>
                                    @if ($item->show_on_website)
                                        <span class="ch-badge ch-badge-success"><span class="dot"></span>Visible</span>
                                    @else
                                        <span class="ch-badge ch-badge-muted"><span class="dot"></span>Hidden</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @can('chatbot.manage')
                                        <a href="{{ route('admin.events.edit', $item) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                        <form method="POST" action="{{ route('admin.events.destroy', $item) }}" class="d-inline" onsubmit="return confirm('Delete this event?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            @include('layouts.admin._empty', [
                                'icon' => 'bi-calendar-event',
                                'message' => 'No events yet',
                                'hint' => 'Generate local holidays and events with AI, or add one manually.',
                                'colspan' => 5,
                                'actionUrl' => auth()->user()->can('chatbot.manage') ? route('admin.events.create') : null,
                                'actionLabel' => 'Add an event',
                            ])
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-3">{{ $items->links() }}</div>
@endsection