@extends('layouts.admin.app')
@section('title', 'Edit user')
@section('content')
<div class="ch-page-header d-flex justify-content-between">
    <h4>Edit user</h4>
    @can('users.delete')
        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Delete this user?')">
            @csrf @method('DELETE')
            <button class="btn btn-danger">Delete</button>
        </form>
    @endcan
</div>
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.users.update', $user) }}">
            @method('PUT')
            @include('admin.users._form')
            <button class="btn btn-ch-primary mt-3">Save</button>
        </form>
    </div>
</div>
@endsection
