@extends('layouts.admin.app')

@section('title', 'Enquiries')

@section('content')
    <div class="ch-page-header">
        <div>
            <div class="ch-breadcrumb">Website / Enquiries</div>
            <h4>Enquiries</h4>
            <p class="ch-subtitle">{{ $items->total() }} enquir{{ $items->total() === 1 ? 'y' : 'ies' }} · {{ $newCount }} unread</p>
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ $errors->first() }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2">
            <form method="GET" action="{{ route('admin.enquiries.index') }}" class="row g-2">
                <div class="col-md-5">
                    <input type="text" name="search" class="form-control" placeholder="Search name, email or message" value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select name="type" class="form-select">
                        <option value="">All types</option>
                        <option value="booking" @selected(request('type') === 'booking')>Booking</option>
                        <option value="contact" @selected(request('type') === 'contact')>Contact</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All statuses</option>
                        <option value="new" @selected(request('status') === 'new')>New</option>
                        <option value="read" @selected(request('status') === 'read')>Read</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-outline-secondary w-100"><i class="bi bi-funnel"></i></button>
                </div>
                @if (request()->has('search') || request()->filled('type') || request()->filled('status'))
                    <div class="col-md-12">
                        <a href="{{ route('admin.enquiries.index') }}" class="btn btn-light btn-sm">Clear filters</a>
                    </div>
                @endif
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Status</th>
                            <th>Name</th>
                            <th>Contact</th>
                            <th>Dates</th>
                            <th>Message</th>
                            <th>Received</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $item)
                            <tr class="{{ $item->status === 'new' ? 'table-warning' : '' }}">
                                <td>
                                    @if ($item->status === 'new')
                                        <span class="ch-badge ch-badge-warning"><span class="dot"></span>New</span>
                                    @else
                                        <span class="ch-badge ch-badge-muted"><span class="dot"></span>Read</span>
                                    @endif
                                    <span class="ch-badge ch-badge-muted ms-1">{{ ucfirst($item->type) }}</span>
                                </td>
                                <td>{{ $item->name }}</td>
                                <td class="small">
                                    <div>{{ $item->email }}</div>
                                    @if ($item->phone)
                                        <div class="text-muted">{{ $item->phone }}</div>
                                    @endif
                                </td>
                                <td class="small">
                                    @if ($item->check_in)
                                        <div>{{ $item->check_in->format('d M Y') }} &rarr; {{ $item->check_out ? $item->check_out->format('d M Y') : '?' }} ({{ $item->nights }} night{{ $item->nights === 1 ? '' : 's' }})</div>
                                        <div class="text-muted">{{ $item->guests }} guests{{ $item->drinks_package ? ' · drinks package' : '' }}{{ $item->terms_accepted ? '' : ' · terms NOT accepted' }}</div>
                                    @else
                                        <span class="text-muted">&mdash;</span>
                                    @endif
                                </td>
                                <td class="text-truncate" style="max-width: 260px;">{{ $item->message ?: '-' }}</td>
                                <td class="small">{{ $item->created_at->format('d M Y, H:i') }}</td>
                                <td class="text-end">
                                    @can('enquiries.update')
                                        @if ($item->status === 'new')
                                            <form method="POST" action="{{ route('admin.enquiries.read', $item) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-primary" title="Mark as read"><i class="bi bi-check-lg"></i></button>
                                            </form>
                                        @endif
                                    @endcan
                                    @can('enquiries.delete')
                                        <form method="POST" action="{{ route('admin.enquiries.destroy', $item) }}" class="d-inline" onsubmit="return confirm('Delete this enquiry?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            @include('layouts.admin._empty', [
                                'icon' => 'bi-inbox',
                                'message' => 'No enquiries yet',
                                'hint' => 'Enquiries from the website booking form and contact page appear here.',
                                'colspan' => 7,
                            ])
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-3">{{ $items->links() }}</div>
@endsection