<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AI\AiAssistantService;
use App\Services\Notification\GuestMessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    public function __construct(
        private readonly AiAssistantService $assistant,
        private readonly GuestMessageService $messages,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
            'session_id' => ['nullable', 'string', 'max:100'],
            'source' => ['nullable', 'in:website,admin'],
        ]);

        $sessionId = $data['session_id'] ?? (string) Str::uuid();
        $result = $this->assistant->ask($data['message'], $sessionId, $data['source'] ?? 'website');

        return response()->json($result);
    }

    public function message(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
            'message' => ['required', 'string', 'max:5000'],
            'session_id' => ['nullable', 'string', 'max:100'],
        ]);

        $result = $this->messages->receive($data);

        return response()->json([
            'ok' => true,
            'auto_replied' => $result['auto_reply'] !== null,
            'reply' => $result['auto_reply']?->body,
        ]);
    }
}
