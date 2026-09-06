@php($item ??= null)

<form method="POST" action="{{ $item ? route('admin.events.update', $item) : route('admin.events.store') }}">
    @csrf
    @if ($item)
        @method('PUT')
    @endif
    <div class="row g-3">
        <div class="col-12">
            <label class="form-label">Title</label>
            <input type="text" name="title" class="form-control" value="{{ old('title', $item?->title) }}" placeholder="e.g. Braunston Canal Festival" required>
            @error('title') <div class="text-danger small">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">Start date</label>
            <input type="date" name="starts_at" class="form-control" value="{{ old('starts_at', $item?->starts_at?->format('Y-m-d')) }}" required>
            @error('starts_at') <div class="text-danger small">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">End date</label>
            <input type="date" name="ends_at" class="form-control" value="{{ old('ends_at', $item?->ends_at?->format('Y-m-d')) }}" required>
            @error('ends_at') <div class="text-danger small">{{ $message }}</div> @enderror
        </div>
        <div class="col-12">
            <label class="form-label">Description</label>
            <textarea name="content" class="form-control" rows="3" placeholder="A short description for your guests">{{ old('content', $item?->content) }}</textarea>
            @error('content') <div class="text-danger small">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Priority</label>
            <input type="number" name="priority" class="form-control" value="{{ old('priority', $item?->priority ?? 1) }}" min="0">
            @error('priority') <div class="text-danger small">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Status</label>
            <select name="status" class="form-select" required>
                <option value="active" @selected(old('status', $item?->status ?? 'active') === 'active')>Active</option>
                <option value="disabled" @selected(old('status', $item?->status ?? 'active') === 'disabled')>Disabled</option>
            </select>
            @error('status') <div class="text-danger small">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-4 d-flex align-items-end">
            <input type="hidden" name="show_on_website" value="0">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="show_on_website" name="show_on_website" value="1" @checked(old('show_on_website', $item?->show_on_website ?? true))>
                <label class="form-check-label" for="show_on_website">Show on public site</label>
            </div>
        </div>
    </div>
    <div class="mt-4 d-flex gap-2">
        <button type="submit" class="btn btn-ch-primary">{{ $item ? 'Save changes' : 'Create event' }}</button>
        <a href="{{ route('admin.events.index') }}" class="btn btn-light">Cancel</a>
    </div>
</form>