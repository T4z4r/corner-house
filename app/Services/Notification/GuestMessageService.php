<?php

namespace App\Services\Notification;

use App\Models\Communication;
use App\Models\Guest;
use App\Services\AI\AiAssistantService;
use App\Services\AI\AiProviderService;

class GuestMessageService
{
    public function __construct(
        private readonly NotificationService $notifications,
        private readonly AiAssistantService $assistant,
        private readonly AiProviderService $provider,
    ) {}

    /**
     * @param  array{name: string, email: string, message: string, session_id?: string}  $data
     * @return array{message: Communication, auto_reply: ?Communication}
     */
    public function receive(array $data): array
    {
        $guest = Guest::query()->firstOrCreate(
            ['email' => $data['email']],
            [
                'first_name' => $this->firstName($data['name']),
                'last_name' => $this->lastName($data['name']),
                'source' => 'website',
                'status' => 'active',
            ],
        );

        $inbound = Communication::create([
            'guest_id' => $guest->id,
            'channel' => 'email',
            'direction' => 'inbound',
            'recipient' => $data['email'],
            'sender_name' => $data['name'],
            'subject' => 'Website message',
            'body' => $data['message'],
            'status' => 'sent',
            'sent_at' => now(),
            'metadata' => [
                'session_id' => $data['session_id'] ?? null,
            ],
        ]);

        $autoReply = null;

        if ($this->provider->isMessageAutoRespondEnabled()) {
            $intent = $this->assistant->detectIntent($data['message']);
            $facts = $this->assistant->factsForIntent($intent, $data['message']);
            $reply = $this->assistant->composeReply($data['message'], $intent, $facts);

            $autoReply = $this->notifications->sendManual([
                'guest_id' => $guest->id,
                'channel' => 'email',
                'direction' => 'outbound',
                'recipient' => $data['email'],
                'subject' => 'Re: your message to Corner House',
                'body' => $reply,
                'metadata' => ['auto_reply' => true, 'in_reply_to' => $inbound->id],
            ]);
        }

        return ['message' => $inbound, 'auto_reply' => $autoReply];
    }

    private function firstName(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];

        return $parts[0] ?? 'Guest';
    }

    private function lastName(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        array_shift($parts);

        return implode(' ', $parts);
    }
}
