@extends('layouts.admin.app')
@section('title', 'Conversation')
@section('content')
<div class="ch-page-header"><div><h4>Conversation</h4></div></div>
<div class="card border-0 shadow-sm">
    <div class="card-body">
        @foreach ($conversation->messages as $message)
            <div class="mb-3">
                <div class="small text-muted">{{ $message->role }} · {{ $message->intent }}</div>
                <div>{{ $message->content }}</div>
                @can('chatbot.manage')
                    @if ($message->role === 'assistant' && ! $message->flagged)
                        <form method="POST" action="{{ route('admin.chatbot.messages.flag', $message) }}">@csrf<button class="btn btn-sm btn-outline-warning mt-1">Flag</button></form>
                    @endif
                @endcan
            </div>
        @endforeach
        @can('chatbot.manage')
            <form method="POST" action="{{ route('admin.chatbot.conversations.reply', $conversation) }}" class="mt-4">
                @csrf
                <label class="form-label">Reply to guest</label>
                <textarea name="message" class="form-control mb-2" rows="3" required></textarea>
                <button class="btn btn-ch-primary">Send reply</button>
            </form>
        @endcan
    </div>
</div>
@endsection
