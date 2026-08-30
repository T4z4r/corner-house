@extends('layouts.admin.app')

@section('title', 'Edit Food & Drink')

@section('content')
    <div class="ch-page-header">
        <div>
            <div class="ch-breadcrumb">Management / Food & Drink / Edit</div>
            <h4>Edit Food & Drink</h4>
            <p class="ch-subtitle">{{ $item->name }}</p>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.food-drink.update', $item) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $item->name) }}" required>
                        @error('name') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-select" required>
                            <option value="">Select category</option>
                            @foreach (['restaurant', 'cafe', 'pub', 'takeaway', 'butcher', 'other'] as $cat)
                                <option value="{{ $cat }}" @selected(old('category', $item->category) === $cat)>{{ ucfirst($cat) }}</option>
                            @endforeach
                        </select>
                        @error('category') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="A short description of this place">{{ old('description', $item->description) }}</textarea>
                        @error('description') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Address</label>
                        <input type="text" name="address" class="form-control" value="{{ old('address', $item->address) }}" placeholder="Full address">
                        @error('address') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control" value="{{ old('phone', $item->phone) }}" placeholder="Phone number">
                        @error('phone') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Website</label>
                        <input type="url" name="website" class="form-control" value="{{ old('website', $item->website) }}" placeholder="https://...">
                        @error('website') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Sort Order</label>
                        <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $item->sort_order) }}" min="0">
                        @error('sort_order') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label">Image <span class="text-muted">(optional)</span></label>
                        @if ($item->image)
                            <div class="mb-2">
                                <img src="{{ asset('storage/'.$item->image) }}" alt="{{ $item->name }}" class="rounded" style="max-height:80px;">
                            </div>
                        @endif
                        <input type="file" name="image" class="form-control" accept="image/*">
                        <div class="form-text">Max 5 MB. Leave empty to keep current image.</div>
                        @error('image') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12">
                        <div class="d-flex gap-4">
                            <div class="form-check form-switch">
                                <input type="hidden" name="is_featured" value="0">
                                <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" value="1" @checked(old('is_featured', $item->is_featured))>
                                <label class="form-check-label" for="is_featured">Featured listing</label>
                            </div>
                            <div class="form-check form-switch">
                                <input type="hidden" name="is_active" value="0">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', $item->is_active))>
                                <label class="form-check-label" for="is_active">Active on public site</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-ch-primary">Save changes</button>
                    <a href="{{ route('admin.food-drink.index') }}" class="btn btn-light">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
