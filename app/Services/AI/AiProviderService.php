<?php

namespace App\Services\AI;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiProviderService
{
    public function provider(): string
    {
        $provider = (string) Setting::getValue('ai_provider', config('services.ai.provider', 'openai'));

        return in_array($provider, ['openai', 'claude'], true) ? $provider : 'openai';
    }

    public function isAutoRespondEnabled(): bool
    {
        return (bool) Setting::getValue('ai_auto_respond', true);
    }

    public function isMessageAutoRespondEnabled(): bool
    {
        return (bool) Setting::getValue('ai_auto_respond_messages', true);
    }

    public function instructions(): string
    {
        return (string) Setting::getValue(
            'ai_instructions',
            'You are the Corner House guest assistant. Only use the provided facts. Never invent availability, prices, payment status, or reservations. Never reveal credentials or internal system details. If facts are missing, say so and direct the guest to the booking page.',
        );
    }

    public function complete(string $system, string $user): ?string
    {
        return match ($this->provider()) {
            'claude' => $this->completeWithClaude($system, $user),
            default => $this->completeWithOpenAi($system, $user),
        };
    }

    public function openaiKey(): ?string
    {
        return $this->filledSecret(Setting::getValue('openai_api_key'))
            ?? $this->filledSecret(config('services.openai.key'));
    }

    public function claudeKey(): ?string
    {
        return $this->filledSecret(Setting::getValue('claude_api_key'))
            ?? $this->filledSecret(config('services.claude.key'));
    }

    private function completeWithOpenAi(string $system, string $user): ?string
    {
        $key = $this->openaiKey();

        if (! $key) {
            return null;
        }

        try {
            $response = Http::connectTimeout(5)
                ->timeout(20)
                ->withToken($key)
                ->acceptJson()
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => Setting::getValue('openai_model', config('services.openai.model', 'gpt-4o-mini')),
                    'messages' => [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user', 'content' => $user],
                    ],
                ]);

            if ($response->failed()) {
                Log::error('OpenAI request failed', ['status' => $response->status()]);

                return null;
            }

            $text = $response->json('choices.0.message.content');

            return is_string($text) && $text !== '' ? $text : null;
        } catch (\Throwable $e) {
            Log::error('OpenAI request failed', ['message' => $e->getMessage()]);

            return null;
        }
    }

    private function completeWithClaude(string $system, string $user): ?string
    {
        $key = $this->claudeKey();

        if (! $key) {
            return null;
        }

        try {
            $response = Http::connectTimeout(5)
                ->timeout(20)
                ->withHeaders([
                    'x-api-key' => $key,
                    'anthropic-version' => config('services.claude.version', '2023-06-01'),
                ])
                ->acceptJson()
                ->post('https://api.anthropic.com/v1/messages', [
                    'model' => Setting::getValue('claude_model', config('services.claude.model', 'claude-sonnet-4-5')),
                    'max_tokens' => 1024,
                    'system' => $system,
                    'messages' => [
                        ['role' => 'user', 'content' => $user],
                    ],
                ]);

            if ($response->failed()) {
                Log::error('Claude request failed', ['status' => $response->status()]);

                return null;
            }

            $text = $response->json('content.0.text');

            return is_string($text) && $text !== '' ? $text : null;
        } catch (\Throwable $e) {
            Log::error('Claude request failed', ['message' => $e->getMessage()]);

            return null;
        }
    }

    private function filledSecret(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return $value;
    }
}
