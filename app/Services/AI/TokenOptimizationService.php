<?php

namespace App\Services\AI;

use App\Models\AiConversation;
use App\Models\AiMessage;
use Illuminate\Support\Collection;

class TokenOptimizationService
{
    private const CHARS_PER_TOKEN = 4;

    private const MAX_HISTORY_MESSAGES = 10;

    private const MAX_FACT_LENGTH = 200;

    private const MAX_FACTS = 5;

    private const MAX_CONTEXT_TOKENS = 3000;

    private const SUMMARY_MARKER = '[Summary of earlier conversation]';

    /**
     * Estimate token count for a string.
     */
    public function estimateTokens(string $text): int
    {
        return max(1, (int) ceil(strlen($text) / self::CHARS_PER_TOKEN));
    }

    /**
     * Build an optimised message history for the context window.
     *
     * Keeps the most recent messages and replaces older ones with a single
     * summary line so the model has conversational context without blowing
     * the token budget.
     *
     * @return array<int, array{role: string, content: string}>
     */
    public function buildOptimisedHistory(AiConversation $conversation, string $currentMessage): array
    {
        $messages = $conversation->messages()
            ->orderBy('id')
            ->get();

        if ($messages->count() <= self::MAX_HISTORY_MESSAGES) {
            return $messages->map(fn (AiMessage $m) => [
                'role' => $m->role === 'assistant' ? 'assistant' : 'user',
                'content' => $m->content,
            ])->all();
        }

        $recent = $messages->slice(-self::MAX_HISTORY_MESSAGES);
        $older = $messages->slice(0, -self::MAX_HISTORY_MESSAGES);

        $summary = $this->summariseMessages($older);

        $history = [
            ['role' => 'user', 'content' => self::SUMMARY_MARKER."\n".$summary],
        ];

        foreach ($recent as $msg) {
            $history[] = [
                'role' => $msg->role === 'assistant' ? 'assistant' : 'user',
                'content' => $msg->content,
            ];
        }

        return $history;
    }

    /**
     * Compress and limit facts to fit within the token budget.
     *
     * @param  array<int, string>  $facts
     * @return array<int, string>
     */
    public function optimiseFacts(array $facts, string $intent): array
    {
        $facts = array_slice($facts, 0, self::MAX_FACTS);

        $optimised = [];
        $tokenBudget = self::MAX_CONTEXT_TOKENS;

        foreach ($facts as $fact) {
            $truncated = $this->truncateFact($fact);
            $tokens = $this->estimateTokens($truncated);

            if ($tokens > $tokenBudget) {
                break;
            }

            $optimised[] = $truncated;
            $tokenBudget -= $tokens;
        }

        return $optimised;
    }

    /**
     * Build a token-efficient prompt for the LLM.
     */
    public function buildPrompt(string $system, string $message, string $intent, array $facts, array $history = []): array
    {
        $optimisedSystem = $this->optimiseSystemPrompt($system);
        $optimisedFacts = $this->optimiseFacts($facts, $intent);
        $context = implode("\n", $optimisedFacts);

        $userContent = "Guest: {$message}\nIntent: {$intent}";

        if ($context !== '') {
            $userContent .= "\nRelevant facts:\n{$context}";
        }

        $messages = [
            ['role' => 'system', 'content' => $optimisedSystem],
        ];

        foreach ($history as $hist) {
            $messages[] = $hist;
        }

        $messages[] = ['role' => 'user', 'content' => $userContent];

        return $messages;
    }

    /**
     * Trim a system prompt to remove redundant whitespace and boilerplate.
     */
    public function optimiseSystemPrompt(string $instructions): string
    {
        $lines = array_filter(
            array_map('trim', explode("\n", $instructions)),
            fn (string $line): bool => $line !== '',
        );

        return implode(' ', array_slice($lines, 0, 20));
    }

    /**
     * Truncate a single fact to a safe length.
     */
    public function truncateFact(string $fact): string
    {
        if (strlen($fact) <= self::MAX_FACT_LENGTH) {
            return $fact;
        }

        return rtrim(substr($fact, 0, self::MAX_FACT_LENGTH)).'…';
    }

    /**
     * Summarise a collection of older messages into a short string.
     *
     * @param  Collection<int, AiMessage>  $messages
     */
    private function summariseMessages(Collection $messages): string
    {
        $intents = $messages->pluck('intent')->filter()->unique()->values()->all();
        $userMessages = $messages->where('role', '!=', 'assistant')->pluck('content')->values();
        $lastUserMsg = $userMessages->last() ?? '';

        $parts = [];

        if ($intents !== []) {
            $parts[] = 'Topics discussed: '.implode(', ', $intents).'.';
        }

        if ($userMessages->isNotEmpty()) {
            $parts[] = 'Guest asked '.$userMessages->count().' question(s). Last: "'.$this->truncateFact($lastUserMsg).'"';
        }

        return $parts !== [] ? implode(' ', $parts) : 'Previous conversation occurred.';
    }

    /**
     * Get token usage stats for a conversation.
     *
     * @return array{total_messages: int, estimated_tokens: int, history_tokens: int, compressed: bool}
     */
    public function getConversationStats(AiConversation $conversation): array
    {
        $messages = $conversation->messages()->get();
        $totalTokens = $messages->sum(fn (AiMessage $m): int => $this->estimateTokens($m->content));
        $historyTokens = $this->estimateTokens(
            $messages->where('role', '!=', 'assistant')->pluck('content')->implode(' ')
        );

        return [
            'total_messages' => $messages->count(),
            'estimated_tokens' => $totalTokens,
            'history_tokens' => $historyTokens,
            'compressed' => $messages->count() > self::MAX_HISTORY_MESSAGES,
        ];
    }
}
