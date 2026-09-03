@extends('layouts.admin.app')

@section('title', 'Edit Review')

@section('content')
    <div class="ch-page-header">
        <div>
            <div class="ch-breadcrumb">Website / Reviews / Edit</div>
            <h4>Edit Review</h4>
            <p class="ch-subtitle">{{ $item->cite ?? 'Guest review' }}</p>
        </div>
        @can('reviews.delete')
            <form method="POST" action="{{ route('admin.reviews.destroy', $item) }}"
                  onsubmit="return confirm('Delete this review?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash me-1"></i>Delete</button>
            </form>
        @endcan
    </div>

    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.reviews.update', $item) }}">
                @csrf
                @method('PUT')
                <div class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label">Stars *</label>
                        <input type="number" name="stars" class="form-control @error('stars') is-invalid @enderror" value="{{ old('stars', $item->stars) }}" min="1" max="5" required>
                        @error('stars') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Guest *</label>
                        <input type="text" name="cite" class="form-control @error('cite') is-invalid @enderror" value="{{ old('cite', $item->cite) }}" placeholder="e.g. Sophie and Tom, June 2026" required>
                        @error('cite') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select @error('status') is-invalid @enderror">
                            <option value="hidden" @selected(old('status', $item->status) === 'hidden')>Hidden</option>
                            <option value="approved" @selected(old('status', $item->status) === 'approved')>Approved</option>
                        </select>
                        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Sort order</label>
                        <input type="number" name="sort_order" class="form-control @error('sort_order') is-invalid @enderror" value="{{ old('sort_order', $item->sort_order) }}" min="0">
                        @error('sort_order') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label">Review *</label>
                        <textarea name="quote" class="form-control @error('quote') is-invalid @enderror" rows="4" required>{{ old('quote', $item->quote) }}</textarea>
                        @error('quote') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-ch-primary">Save changes</button>
                    <a href="{{ route('admin.reviews.index') }}" class="btn btn-light">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
