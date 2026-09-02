@extends('layouts.admin.app')

@section('title', 'Message')

@section('content')
    <div class="ch-page-header">
        <div>
            <div class="ch-breadcrumb">
                <a href="{{ route('admin.messages.index') }}">Management / Messages</a>
            </div>
            <h4>Message detail</h4>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.messages.index') }}" class="btn btn-light"><i class="bi bi-arrow-left me-1"></i>Back</a>
            @can('channels.sync')
                <form method="POST" action="{{ route('admin.messages.read', $message) }}">
                    @csrf
                    <button class="btn btn-outline-secondary"><i class="bi bi-check2-circle me-1"></i>Mark read</button>
                </form>
            @endcan
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex align-items-center gap-2 mb-3">
                <span class="ch-badge {{ $message->direction === 'inbound' ? 'ch-badge-info' : 'ch-badge-muted' }}">
                    {{ ucfirst($message->direction) }}
                </span>
                <span class="ch-badge {{ $message->status === 'pending' ? 'ch-badge-warning' : 'ch-badge-success' }}">
                    <span class="dot"></span>{{ $message->status === 'pending' ? 'Unread' : 'Read' }}
                </span>
                <span class="text-muted small"><i class="bi bi-clock me-1"></i>{{ $message->sent_at?->format('d M Y H:i') ?? '-' }}</span>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <div class="ch-detail-label mb-1">From / To</div>
                    <div class="fw-semibold">
                        @if ($message->direction === 'inbound')
                            {{ $message->sender_name ?? 'Guest' }}
                        @else
                            {{ $message->reservation?->guest?->full_name ?? 'Corner House' }}
                        @endif
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="ch-detail-label mb-1">Booking</div>
                    @if ($message->reservation)
                        <a href="{{ route('admin.reservations.show', $message->reservation) }}">{{ $message->reservation->reference }}</a>
                        <span class="text-muted small"> · {{ $message->reservation->room?->name ?? 'Room' }}</span>
                    @else
                        <span class="text-muted">Not linked to a local booking</span>
                    @endif
                </div>
                <div class="col-md-6">
                    <div class="ch-detail-label mb-1">Subject</div>
                    <div>{{ $message->subject }}</div>
                </div>
                <div class="col-md-6">
                    <div class="ch-detail-label mb-1">Channel</div>
                    <div>{{ ucfirst($message->channel) }} <span class="text-muted small">via Beds24</span></div>
                </div>
            </div>

            <div class="p-3 rounded mb-3" style="background: #f8f9fa; white-space: pre-wrap;">{{ $message->body }}</div>

            @if (!empty($message->metadata['beds24_booking_id']))
                <div class="small text-muted">
                    Beds24 message #{{ $message->metadata['beds24_message_id'] ?? '-' }} · booking #{{ $message->metadata['beds24_booking_id'] }}
                </div>
            @endif
        </div>
    </div>

    @if ($message->direction === 'inbound' && !empty($message->metadata['beds24_booking_id']))
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Reply via Beds24</div>
            <div class="card-body">
                @can('communications.send')
                    <form method="POST" action="{{ route('admin.messages.reply', $message) }}">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label">Message *</label>
                            <textarea name="body" class="form-control" rows="4" placeholder="Your reply will be sent to the guest's channel inbox (Airbnb / Booking.com etc)." required>{{ old('body') }}</textarea>
                        </div>
                        @if ($errors->any())
                            <div class="alert alert-danger py-2 small">{{ $errors->first() }}</div>
                        @endif
                        <button class="btn btn-ch-primary"><i class="bi bi-send me-1"></i>Send reply</button>
                    </form>
                @else
                    <div class="text-muted small">You do not have permission to send replies.</div>
                @endcan
            </div>
        </div>
    @endif
@endsection
