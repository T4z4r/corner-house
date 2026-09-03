@extends('layouts.admin.app')

@section('title', 'New Review')

@section('content')
    <div class="ch-page-header">
        <div>
            <div class="ch-breadcrumb">Website / Reviews / New</div>
            <h4>New Review</h4>
            <p class="ch-subtitle">Add a guest review to show on the website</p>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.reviews.store') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label">Stars *</label>
                        <input type="number" name="stars" class="form-control @error('stars') is-invalid @enderror" value="{{ old('stars', 5) }}" min="1" max="5" required>
                        @error('stars') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Guest *</label>
                        <input type="text" name="cite" class="form-control @error('cite') is-invalid @enderror" value="{{ old('cite') }}" placeholder="e.g. Sophie and Tom, June 2026" required>
                        @error('cite') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select @error('status') is-invalid @enderror">
                            <option value="hidden" @selected(old('status', 'hidden') === 'hidden')>Hidden</option>
                            <option value="approved" @selected(old('status', 'hidden') === 'approved')>Approved</option>
                        </select>
                        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Sort order</label>
                        <input type="number" name="sort_order" class="form-control @error('sort_order') is-invalid @enderror" value="{{ old('sort_order', 0) }}" min="0">
                        @error('sort_order') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label">Review *</label>
                        <textarea name="quote" class="form-control @error('quote') is-invalid @enderror" rows="4" placeholder="A short quote from the guest" required>{{ old('quote') }}</textarea>
                        @error('quote') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-ch-primary">Create review</button>
                    <a href="{{ route('admin.reviews.index') }}" class="btn btn-light">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
