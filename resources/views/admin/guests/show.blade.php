@extends('layouts.admin.app')

@section('title', 'Guest Profile')

@section('content')
    <div class="ch-page-header">
        <div>
            <div class="ch-breadcrumb"><a href="{{ route('admin.guests.index') }}">Guests</a> / Profile</div>
            <h4>Guest Profile</h4>
            <p class="ch-subtitle">{{ $guest->email ?? 'No email on file' }}</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @if ($guest->phone)
                <a href="tel:{{ preg_replace('/[^+\d]/', '', $guest->phone) }}" class="btn btn-outline-success btn-sm"><i class="bi bi-telephone me-1"></i>Call</a>
            @endif
            @if ($guest->email)
                <a href="mailto:{{ $guest->email }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-envelope me-1"></i>Send mail</a>
            @endif
            @can('guests.update')
                <a href="{{ route('admin.guests.edit', $guest) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil me-1"></i>Edit</a>
            @endcan
            @can('guests.delete')
                <form method="POST" action="{{ route('admin.guests.destroy', $guest) }}"
                      onsubmit="return confirm('Delete this guest?');">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash me-1"></i>Delete</button>
                </form>
            @endcan
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="ch-avatar"><i class="bi bi-person-fill"></i></div>
                    <h5 class="mt-3 mb-0">{{ $guest->full_name }}</h5>
                    <div class="mt-2">
                        <span class="ch-badge ch-badge-{{ $guest->status === 'active' ? 'success' : ($guest->status === 'blacklisted' ? 'danger' : 'muted') }}">
                            <span class="dot"></span>{{ ucfirst($guest->status) }}
                        </span>
                    </div>
                    <hr>
                    <ul class="list-unstyled mb-0 text-start small">
                        <li class="mb-2 d-flex align-items-center justify-content-between">
                            <span><i class="bi bi-envelope me-2"></i>{{ $guest->email ?? '-' }}</span>
                            @if ($guest->email)
                                <a href="mailto:{{ $guest->email }}" class="text-decoration-none" title="Send mail"><i class="bi bi-send"></i></a>
                            @endif
                        </li>
                        <li class="mb-2 d-flex align-items-center justify-content-between">
                            <span><i class="bi bi-telephone me-2"></i>{{ $guest->phone ?? '-' }}</span>
                            @if ($guest->phone)
                                <a href="tel:{{ preg_replace('/[^+\d]/', '', $guest->phone) }}" class="text-decoration-none" title="Call"><i class="bi bi-telephone-outbound"></i></a>
                            @endif
                        </li>
                        <li class="mb-2"><i class="bi bi-geo-alt me-2"></i>{{ $guest->country ?? '-' }}</li>
                        <li class="mb-2"><i class="bi bi-globe me-2"></i>{{ strtoupper($guest->language) }}</li>
                        <li><i class="bi bi-box-arrow-in-down me-2"></i>{{ $guest->source ?? 'direct' }}</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white"><h6 class="mb-0">Booking History</h6></div>
                <div class="card-body p-0">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr><th>Reference</th><th>Room</th><th>Dates</th><th>Total</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($guest->reservations as $reservation)
                                <tr>
                                    <td>{{ $reservation->reference }}</td>
                                    <td>{{ $reservation->room?->name ?? '-' }}</td>
                                    <td>{{ $reservation->check_in->format('d M Y') }} → {{ $reservation->check_out->format('d M Y') }}</td>
                                    <td>&pound;{{ number_format($reservation->total_amount, 2) }}</td>
                                    <td><span class="ch-badge ch-badge-{{ $reservation->status === 'cancelled' ? 'danger' : 'muted' }}"><span class="dot"></span>{{ ucfirst($reservation->status) }}</span></td>
                                </tr>
                            @empty
                                @include('layouts.admin._empty', [
                                    'icon' => 'bi-journal-x',
                                    'message' => 'No bookings yet',
                                    'hint' => 'This guest has not stayed with you yet.',
                                    'colspan' => 5,
                                ])
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-header bg-white"><h6 class="mb-0">Communication history</h6></div>
                <div class="card-body p-0">
                    <table class="table mb-0">
                        <thead class="table-light"><tr><th>Subject</th><th>Channel</th><th>Status</th><th>Sent</th></tr></thead>
                        <tbody>
                            @forelse ($guest->communications as $communication)
                                <tr>
                                    <td>{{ $communication->subject }}</td>
                                    <td>{{ $communication->channel }}</td>
                                    <td>{{ $communication->status }}</td>
                                    <td>{{ $communication->sent_at?->diffForHumans() ?? '-' }}</td>
                                </tr>
                            @empty
                                @include('layouts.admin._empty', [
                                    'icon' => 'bi-chat',
                                    'message' => 'No messages yet',
                                    'hint' => 'Emails and other guest messages will appear here.',
                                    'colspan' => 4,
                                ])
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
