@extends('layouts.admin.app')
@section('title', 'Users')
@section('content')
<div class="ch-page-header">
    <div>
        <div class="ch-breadcrumb">System / Users</div>
        <h4>Users</h4>
    </div>
    @can('users.create')
        <a href="{{ route('admin.users.create') }}" class="btn btn-ch-primary"><i class="bi bi-plus-lg me-1"></i>New user</a>
    @endcan
</div>
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>Name</th><th>Email</th><th>Role</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->roles->pluck('name')->join(', ') }}</td>
                        <td class="text-end">
                            @can('users.update')
                                <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    @include('layouts.admin._empty', ['icon' => 'bi-people', 'message' => 'No users', 'hint' => '', 'colspan' => 4])
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $users->links() }}</div>
@endsection
