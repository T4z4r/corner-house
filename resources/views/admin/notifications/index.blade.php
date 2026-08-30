@extends('layouts.admin.app')

@section('title', 'System notifications')

@section('content')
    <div class="ch-page-header">
        <div>
            <div class="ch-breadcrumb">
                <a href="{{ route('admin.dashboard') }}">Dashboard</a>
                <span class="mx-1">/</span>
                Notifications
            </div>
            <h4>System notifications</h4>
            <p class="ch-subtitle">Track booking, payment, and communication activity in one place.</p>
        </div>
        <form method="POST" action="{{ route('admin.notifications.mark-all-read') }}">
            @csrf
            <button type="submit" class="btn btn-ch-primary">
                <i class="bi bi-check2-all me-1"></i> Mark all read
            </button>
        </form>
    </div>

    <div class="card">
        <div class="card-body p-0">
            @forelse ($notifications as $notification)
                @php
                    $level = $notification->data['level'] ?? 'info';
                    $icon = $notification->data['icon'] ?? 'bi-bell';
                    $badgeClass = match ($level) {
                        'success' => 'text-bg-success',
                        'warning' => 'text-bg-warning',
                        'danger' => 'text-bg-danger',
                        default => 'text-bg-info',
                    };
                @endphp
                <a class="text-decoration-none d-block border-bottom px-3 py-3 {{ is_null($notification->read_at) ? 'bg-light' : '' }}" href="{{ $notification->data['url'] ?? '#' }}">
                    <div class="d-flex gap-3">
                        <div class="ch-notification-icon {{ $badgeClass }}">
                            <i class="bi {{ $icon }}"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-start justify-content-between gap-2">
                                <h6 class="mb-1 text-dark">{{ $notification->data['title'] ?? 'Notification' }}</h6>
                                @if (is_null($notification->read_at))
                                    <span class="badge text-bg-primary-subtle text-primary border border-primary-subtle">New</span>
                                @endif
                            </div>
                            <p class="mb-1 text-muted">{{ $notification->data['message'] ?? '' }}</p>
                            <small class="text-muted">{{ $notification->created_at?->format('d M Y, H:i') }}</small>
                        </div>
                    </div>
                </a>
            @empty
                <div class="ch-empty">
                    <i class="bi bi-bell-slash"></i>
                    <p class="lead mb-1">No notifications yet</p>
                    <p class="mb-0">New activity will appear here as it happens.</p>
                </div>
            @endforelse
        </div>
        @if ($notifications->hasPages())
            <div class="card-footer bg-white">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
@endsection
