@extends('layouts.admin.app')
@section('title', 'New user')
@section('content')
<div class="ch-page-header"><div><h4>New user</h4></div></div>
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.users.store') }}">
            @include('admin.users._form')
            <button class="btn btn-ch-primary mt-3">Create user</button>
        </form>
    </div>
</div>
@endsection
