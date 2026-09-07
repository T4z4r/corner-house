<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChannelAccount;
use App\Models\Communication;
use App\Services\AI\AiAssistantService;
use App\Services\Audit\AuditLogger;
use App\Services\Beds24\Beds24MessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MessageInboxController extends Controller
{
    public function __construct(
        private readonly Beds24MessageService $messages,
        private readonly AuditLogger $auditLogger,
        private readonly AiAssistantService $assistant,
    ) {}

    public function index(Request $request): View
    {
        $query = Communication::query()
            ->where('channel', 'beds24')
            ->with(['guest', 'reservation']);

        if ($direction = $request->query('direction')) {
            $query->where('direction', $direction);
        }
        if ($unread = $request->boolean('unread')) {
            $query->where('status', 'pending');
        }
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search): void {
                $q->where('body', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhereHas('reservation', fn ($r) => $r->where('reference', 'like', "%{$search}%"));
            });
        }

        $messages = $query->latest('sent_at')->paginate(25)->withQueryString();

        return view('admin.messages.index', [
            'messages' => $messages,
            'unreadCount' => Communication::query()->where('channel', 'beds24')->where('status', 'pending')->count(),
            'hasAccount' => ChannelAccount::query()->where('provider', 'beds24')->where('status', 'active')->exists(),
        ]);
    }

    public function show(Communication $message): View
    {
        abort_unless($message->channel === 'beds24', 404);

        return view('admin.messages.show', [
            'message' => $message->load(['guest', 'reservation' => fn ($q) => $q->with(['room', 'guest'])]),
        ]);
    }

    public function markRead(Communication $message): RedirectResponse
    {
        abort_unless($message->channel === 'beds24', 404);

        $message->update(['status' => 'sent', 'metadata' => array_merge($message->metadata ?? [], ['read' => true])]);

        return back()->with('status', 'Message marked as read.');
    }

    public function fetch(Request $request): RedirectResponse
    {
        $account = ChannelAccount::query()->where('provider', 'beds24')->where('status', 'active')->first();

        if (! $account) {
            return back()->withErrors(['error' => 'No active Beds24 account configured.']);
        }

        $summary = $this->messages->syncAccount($account);
        $this->auditLogger->log('messages.fetched', 'messages', 'channel_account', (string) $account->id);

        return redirect()->route('admin.messages.index')
            ->with('status', "Fetched {$summary['total']} message(s): {$summary['created']} new, {$summary['updated']} updated, {$summary['failed']} failed.");
    }

    public function reply(Request $request, Communication $message): RedirectResponse
    {
        abort_unless($message->channel === 'beds24', 404);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:4000'],
        ]);

        try {
            $this->messages->reply($message, $data['body']);
            $this->auditLogger->log('messages.replied', 'messages', 'communication', (string) $message->id);

            return redirect()->route('admin.messages.show', $message)->with('status', 'Reply sent.');
        } catch (\DomainException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function draft(Communication $message): JsonResponse
    {
        abort_unless($message->channel === 'beds24' && $message->direction === 'inbound', 404);

        $draft = $this->assistant->draftReply(
            guestMessage: $message->body,
            senderName: $message->sender_name,
            reservation: $message->reservation,
        );

        if (! $draft) {
            return response()->json([
                'error' => 'The AI draft is unavailable right now. Check the AI API key is configured, then try again.',
            ], 422);
        }

        $this->auditLogger->log('messages.drafted', 'messages', 'communication', (string) $message->id);

        return response()->json(['draft' => $draft]);
    }
}
