<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\KnowledgeBaseArticle;
use App\Services\AI\AiAssistantService;
use App\Services\AI\AiProviderService;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChatbotController extends Controller
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly AiAssistantService $assistant,
        private readonly AiProviderService $provider,
    ) {}

    public function index(): View
    {
        return view('admin.chatbot.index', [
            'articles' => KnowledgeBaseArticle::query()->latest()->paginate(20, ['*'], 'articles'),
            'conversations' => AiConversation::query()->withCount('messages')->latest()->paginate(15, ['*'], 'conversations'),
            'provider' => $this->provider->provider(),
            'autoRespond' => $this->provider->isAutoRespondEnabled(),
        ]);
    }

    public function storeArticle(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'category' => ['required', 'string', 'max:100'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'priority' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'in:active,disabled'],
        ]);

        KnowledgeBaseArticle::create([
            ...$data,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        $this->auditLogger->log('chatbot.article_created', 'chatbot');

        return back()->with('status', 'Knowledge article saved.');
    }

    public function updateArticle(Request $request, KnowledgeBaseArticle $article): RedirectResponse
    {
        $data = $request->validate([
            'category' => ['required', 'string', 'max:100'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'priority' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'in:active,disabled'],
        ]);

        $article->update([
            ...$data,
            'updated_by' => auth()->id(),
        ]);

        $this->auditLogger->log('chatbot.article_updated', 'chatbot', 'knowledge_base_article', (string) $article->id);

        return back()->with('status', 'Article updated.');
    }

    public function flagMessage(AiMessage $message): RedirectResponse
    {
        $message->update(['flagged' => true]);

        return back()->with('status', 'Response flagged.');
    }

    public function showConversation(AiConversation $conversation): View
    {
        return view('admin.chatbot.conversation', [
            'conversation' => $conversation->load('messages'),
        ]);
    }

    public function reply(Request $request, AiConversation $conversation): RedirectResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $this->assistant->replyAsStaff($conversation, $data['message']);
        $this->auditLogger->log('chatbot.replied', 'chatbot', 'ai_conversation', (string) $conversation->id);

        return back()->with('status', 'Reply sent.');
    }
}
