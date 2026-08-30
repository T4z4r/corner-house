@extends('layouts.admin.app')

@section('title', 'Conversation')

@section('content')
<div class="ch-page-header">
    <div><h4>Conversation</h4></div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        @foreach ($conversation->messages as $message)
            <div class="mb-3">
                <div class="small text-muted">{{ $message->role }} - {{ $message->intent }}</div>
                <div>{{ $message->content }}</div>

                @if ($message->faqArticle)
                    <div class="small text-muted mt-1">
                        Saved as FAQ
                        @if ($message->faqArticle->show_on_website)
                            - visible on website
                        @else
                            - hidden from website
                        @endif
                    </div>
                @endif

                @can('chatbot.manage')
                    @if ($message->role === 'assistant' && ! $message->flagged)
                        <form method="POST" action="{{ route('admin.chatbot.messages.flag', $message) }}">
                            @csrf
                            <button class="btn btn-sm btn-outline-warning mt-1">Flag</button>
                        </form>
                    @endif

                    @if ($message->role === 'user' && ! $message->faqArticle)
                        <form method="POST" action="{{ route('admin.chatbot.articles.store') }}" class="border rounded p-3 mt-3">
                            @csrf
                            <input type="hidden" name="category" value="faqs">
                            <input type="hidden" name="title" value="{{ $message->content }}">
                            <input type="hidden" name="source_message_id" value="{{ $message->id }}">
                            <input type="hidden" name="status" value="active">
                            <input type="hidden" name="show_on_website" value="0">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="show_on_website" value="1" id="showFaq{{ $message->id }}" checked>
                                <label class="form-check-label" for="showFaq{{ $message->id }}">Show on website</label>
                            </div>
                            <label class="form-label small text-muted">FAQ answer</label>
                            <textarea name="content" class="form-control mb-2" rows="3" placeholder="Write the public answer for this question..." required></textarea>
                            <button class="btn btn-sm btn-ch-primary">Save as FAQ</button>
                        </form>
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
