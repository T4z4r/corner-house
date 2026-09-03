@extends('layouts.admin.app')

@section('title', 'Reviews')

@section('content')
    <div class="ch-page-header">
        <div>
            <div class="ch-breadcrumb">Website / Reviews</div>
            <h4>Reviews</h4>
            <p class="ch-subtitle">{{ $items->total() }} review{{ $items->total() === 1 ? '' : 's' }} · {{ $approvedCount }} approved · {{ $hiddenCount }} hidden</p>
        </div>
        @can('reviews.create')
            <a href="{{ route('admin.reviews.create') }}" class="btn btn-ch-primary"><i class="bi bi-plus-lg me-1"></i>Add review</a>
        @endcan
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

    @can('reviews.create')
        @if ($accounts->isNotEmpty())
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.reviews.import') }}" class="row g-3 align-items-end">
                        @csrf
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Import from Airbnb</label>
                            <select name="account_id" class="form-select" required>
                                <option value="">Select Beds24 account</option>
                                @foreach ($accounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">&nbsp;</label>
                            <select name="beds24_room_id" class="form-select" required>
                                <option value="">Select linked room</option>
                                @foreach ($beds24Rooms as $room)
                                    <option value="{{ $room['beds24_room_id'] }}">{{ $room['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-download me-1"></i>Import reviews as hidden</button>
                        </div>
                        <div class="col-12">
                            <div class="small text-muted">Pulls live Airbnb reviews from Beds24. New reviews are stored as hidden and appear on the website only after you approve them.</div>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    @endcan

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2">
            <form method="GET" action="{{ route('admin.reviews.index') }}" class="row g-2">
                <div class="col-md-5">
                    <input type="text" name="search" class="form-control" placeholder="Search reviews or names" value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All statuses</option>
                        <option value="approved" @selected(request('status') === 'approved')>Approved</option>
                        <option value="hidden" @selected(request('status') === 'hidden')>Hidden</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-secondary w-100"><i class="bi bi-funnel me-1"></i>Filter</button>
                </div>
                @if (request()->has('search') || request()->filled('status'))
                    <div class="col-md-2">
                        <a href="{{ route('admin.reviews.index') }}" class="btn btn-light w-100">Clear</a>
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
                            <th>Stars</th>
                            <th>Review</th>
                            <th>Guest</th>
                            <th>Source</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $item)
                            <tr>
                                <td>
                                    <span class="small text-warning">
                                        @for ($i = 1; $i <= 5; $i++)
                                            <i class="bi {{ $i <= $item->stars ? 'bi-star-fill' : 'bi-star' }}"></i>
                                        @endfor
                                    </span>
                                </td>
                                <td class="text-truncate" style="max-width: 320px;">{{ Str::limit($item->quote, 80) }}</td>
                                <td>{{ $item->cite ?? '-' }}</td>
                                <td>
                                    <span class="ch-badge ch-badge-muted">{{ ucfirst($item->source ?? 'manual') }}</span>
                                </td>
                                <td>
                                    @can('reviews.update')
                                        <form method="POST" action="{{ route('admin.reviews.toggle', $item) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm {{ $item->status === 'approved' ? 'btn-outline-success' : 'btn-outline-secondary' }}" title="{{ $item->status === 'approved' ? 'Hide' : 'Approve' }}">
                                                <i class="bi {{ $item->status === 'approved' ? 'bi-toggle-on' : 'bi-toggle-off' }}"></i>
                                                <span class="d-none d-md-inline ms-1">{{ $item->status === 'approved' ? 'Approved' : 'Hidden' }}</span>
                                            </button>
                                        </form>
                                    @else
                                        <span class="ch-badge ch-badge-{{ $item->status === 'approved' ? 'success' : 'muted' }}">
                                            <span class="dot"></span>{{ ucfirst($item->status) }}
                                        </span>
                                    @endcan
                                </td>
                                <td class="text-end">
                                    @can('reviews.update')
                                        <a href="{{ route('admin.reviews.edit', $item) }}" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                                    @endcan
                                    @can('reviews.delete')
                                        <form method="POST" action="{{ route('admin.reviews.destroy', $item) }}" class="d-inline" onsubmit="return confirm('Delete this review?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            @include('layouts.admin._empty', [
                                'icon' => 'bi-chat-quote',
                                'message' => 'No reviews yet',
                                'hint' => 'Add a review or import them from Airbnb to show on the website.',
                                'colspan' => 6,
                                'actionUrl' => auth()->user()->can('reviews.create') ? route('admin.reviews.create') : null,
                                'actionLabel' => 'Add a review',
                            ])
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-3">{{ $items->links() }}</div>
@endsection
