@extends('layouts.admin.app')

@section('title', 'Edit Place of Interest')

@section('content')
    <div class="ch-page-header">
        <div>
            <div class="ch-breadcrumb">Management / Places of Interest / Edit</div>
            <h4>Edit Place of Interest</h4>
            <p class="ch-subtitle">{{ $item->name }}</p>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.places.update', $item) }}" enctype="multipart/form-data">
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
                            @foreach (['attraction', 'town', 'nature', 'activity', 'shop', 'transport', 'other'] as $cat)
                                <option value="{{ $cat }}" @selected(old('category', $item->category) === $cat)>{{ ucfirst($cat) }}</option>
                            @endforeach
                        </select>
                        @error('category') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="A short description for your guests">{{ old('description', $item->description) }}</textarea>
                        @error('description') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Address</label>
                        <input type="text" name="address" class="form-control" value="{{ old('address', $item->address) }}">
                        @error('address') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Distance</label>
                        <input type="text" name="distance" class="form-control" value="{{ old('distance', $item->distance) }}" placeholder="e.g. 2 miles">
                        @error('distance') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Website</label>
                        <input type="url" name="website" class="form-control" value="{{ old('website', $item->website) }}" placeholder="https://">
                        @error('website') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control" value="{{ old('phone', $item->phone) }}">
                        @error('phone') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Sort Order</label>
                        <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $item->sort_order) }}" min="0">
                        @error('sort_order') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <input type="hidden" name="is_active" value="0">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', $item->is_active))>
                            <label class="form-check-label" for="is_active">Active on public site</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Image <span class="text-muted">(optional)</span></label>
                        @include('admin.partials.single-image-dropzone', [
                            'dzId' => 'places-image',
                            'currentImage' => $item->image,
                            'itemName' => $item->name,
                            'uploadRoute' => route('admin.places.upload-image'),
                            'deleteRoute' => route('admin.places.delete-uploaded-image'),
                        ])
                        @error('image') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-ch-primary">Save changes</button>
                    <a href="{{ route('admin.places.index') }}" class="btn btn-light">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
