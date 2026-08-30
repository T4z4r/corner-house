@csrf
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Name</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $user->name ?? '') }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" value="{{ old('email', $user->email ?? '') }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Password @if(isset($user))<span class="text-muted">(leave blank to keep)</span>@endif</label>
        <input type="password" name="password" class="form-control" @unless(isset($user)) required @endunless>
    </div>
    <div class="col-md-6">
        <label class="form-label">Role</label>
        <select name="role" class="form-select" required>
            @foreach ($roles as $role)
                <option value="{{ $role->name }}" @selected(old('role', isset($user) ? $user->roles->first()?->name : null) === $role->name)>{{ $role->name }}</option>
            @endforeach
        </select>
    </div>
</div>
