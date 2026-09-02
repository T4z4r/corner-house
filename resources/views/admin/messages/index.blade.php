@extends('layouts.admin.app')

@section('title', 'Messages')

@section('content')
    <div class="ch-page-header">
        <div>
            <div class="ch-breadcrumb">Management / Messages</div>
            <h4>Messages <span class="fs-6 text-muted fw-normal">({{ $messages->total() }})</span></h4>
            @if ($unreadCount > 0)
                <p class="ch-subtitle">{{ $unreadCount }} unread message{{ $unreadCount === 1 ? '' : 's' }}</p>
            @else
                <p class="ch-subtitle">All caught up.</p>
            @endif
        </div>
        @can('channels.sync')
            <form method="POST" action="{{ route('admin.messages.fetch') }}">
                @csrf
                <button class="btn btn-ch-primary" @disabled(!$hasAccount)><i class="bi bi-arrow-down-circle me-1"></i>Fetch from Beds24</button>
            </form>
        @endcan
    </div>

    @if (!$hasAccount)
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle me-2"></i>No active Beds24 account is configured. Connect one on the
            <a href="{{ route('admin.channels.integrations') }}">Beds24 integrations</a> page to fetch messages.
        </div>
    @endif

    <div class="ch-toolbar mb-3">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-12 col-md-5">
                <div class="position-relative">
                    <i class="bi bi-search position-absolute top-50 translate-middle-y ms-3 text-muted"></i>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control ps-5" placeholder="Search message or booking reference...">
                </div>
            </div>
            <div class="col-6 col-md-2">
                <select name="direction" class="form-select">
                    <option value="">All directions</option>
                    <option value="inbound" @selected(request('direction') === 'inbound')>Inbound</option>
                    <option value="outbound" @selected(request('direction') === 'outbound')>Outbound</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <select name="unread" class="form-select">
                    <option value="">All statuses</option>
                    <option value="1" @selected(request('unread') === '1')>Unread only</option>
                </select>
            </div>
            <div class="col-12 col-md-3 d-flex gap-2">
                <button class="btn btn-ch-primary"><i class="bi bi-funnel me-1"></i>Apply</button>
                @if (request('search') || request('direction') || request('unread'))
                    <a href="{{ route('admin.messages.index') }}" class="btn btn-light">Clear</a>
                @endif
            </div>
        </form>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th></th>
                            <th>From / To</th>
                            <th>Booking</th>
                            <th>Subject</th>
                            <th>Message</th>
                            <th>Sent</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($messages as $message)
                            <tr class="{{ $message->status === 'pending' ? 'fw-semibold' : '' }}">
                                <td>
                                    <span class="ch-badge {{ $message->direction === 'inbound' ? 'ch-badge-info' : 'ch-badge-muted' }}">
                                        {{ $message->direction === 'inbound' ? 'In' : 'Out' }}
                                    </span>
                                </td>
                                <td>{{ $message->direction === 'inbound' ? ($message->sender_name ?? 'Guest') : ($message->reservation?->guest?->full_name ?? 'You') }}</td>
                                <td>
                                    @if ($message->reservation)
                                        <a href="{{ route('admin.reservations.show', $message->reservation) }}" class="text-decoration-none">{{ $message->reservation->reference }}</a>
                                    @else
                                        <span class="text-muted">Beds24</span>
                                    @endif
                                </td>
                                <td class="small">{{ $message->subject }}</td>
                                <td class="text-muted small text-truncate" style="max-width: 220px;">{{ $message->body }}</td>
                                <td class="small text-muted">{{ $message->sent_at?->diffForHumans() ?? '-' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('admin.messages.show', $message) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                                    @can('channels.sync')
                                        <form method="POST" action="{{ route('admin.messages.read', $message) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-secondary"><i class="bi bi-check2-circle"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            @include('layouts.admin._empty', [
                                'icon' => 'bi-chat-dots',
                                'message' => 'No messages',
                                'hint' => 'Fetch messages from Beds24 to populate this inbox.',
                                'colspan' => 7,
                            ])
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-3">{{ $messages->links() }}</div>
@endsection
