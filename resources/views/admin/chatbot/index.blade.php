@extends('layouts.admin.app')
@section('title', 'AI Assistant')
@section('content')
<div class="ch-page-header">
    <div>
        <h4>AI guest assistant</h4>
        <p class="ch-subtitle mb-0">
            Provider: {{ ucfirst($provider) }}
            · Auto-respond: {{ $autoRespond ? 'on' : 'off' }}
            · Configure keys in <a href="{{ route('admin.settings') }}">Settings</a>
        </p>
    </div>
</div>
<div class="row g-3">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">Knowledge base</div>
            <div class="card-body">
                @can('chatbot.manage')
                    <form method="POST" action="{{ route('admin.chatbot.articles.store') }}" class="row g-2 mb-3">
                        @csrf
                        <div class="col-md-4"><input name="category" class="form-control" placeholder="Category" required></div>
                        <div class="col-md-8"><input name="title" class="form-control" placeholder="Title" required></div>
                        <div class="col-12"><textarea name="content" class="form-control" rows="3" required></textarea></div>
                        <div class="col-md-4"><select name="status" class="form-select"><option value="active">Active</option><option value="disabled">Disabled</option></select></div>
                        <div class="col-md-4"><input type="number" name="priority" class="form-control" value="0"></div>
                        <div class="col-md-4"><button class="btn btn-ch-primary w-100">Add article</button></div>
                    </form>
                @endcan
                @foreach ($articles as $article)
                    <div class="border-bottom py-2">
                        <strong>{{ $article->title }}</strong>
                        <span class="badge text-bg-light">{{ $article->category }}</span>
                        <span class="small text-muted">{{ $article->status }}</span>
                    </div>
                @endforeach
                <div class="mt-3">{{ $articles->links() }}</div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">Conversations</div>
            <div class="list-group list-group-flush">
                @forelse ($conversations as $conversation)
                    <a class="list-group-item" href="{{ route('admin.chatbot.conversations.show', $conversation) }}">
                        {{ $conversation->session_id }}
                        <span class="small text-muted">{{ $conversation->messages_count }} messages</span>
                    </a>
                @empty
                    <div class="p-3 text-muted">No conversations yet.</div>
                @endforelse
            </div>
        </div>
        <div class="mt-3">{{ $conversations->links() }}</div>
    </div>
</div>
@endsection
