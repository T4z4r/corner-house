<?php

namespace App\Services\AI;

use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\Property;
use App\Models\Room;
use App\Services\Availability\AvailabilityService;
use App\Services\Pricing\PricingEngine;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class AiAssistantService
{
    public function __construct(
        private readonly KnowledgeBaseService $knowledgeBase,
        private readonly AvailabilityService $availability,
        private readonly PricingEngine $pricing,
        private readonly AiProviderService $provider,
    ) {}

    /**
     * @return array{reply: ?string, intent: string, conversation_id: int, auto_responded: bool}
     */
    public function ask(string $message, string $sessionId, string $source = 'website'): array
    {
        $conversation = AiConversation::query()->firstOrCreate(
            ['session_id' => $sessionId, 'status' => 'open'],
            ['source' => $source],
        );

        $intent = $this->detectIntent($message);

        AiMessage::create([
            'ai_conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $message,
            'intent' => $intent,
        ]);

        if (! $this->provider->isAutoRespondEnabled()) {
            return [
                'reply' => 'Thanks — a member of the team will reply to you shortly.',
                'intent' => $intent,
                'conversation_id' => $conversation->id,
                'auto_responded' => false,
            ];
        }

        $facts = $this->factsForIntent($intent, $message);
        $reply = $this->composeReply($message, $intent, $facts);

        AiMessage::create([
            'ai_conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => $reply,
            'intent' => $intent,
        ]);

        return [
            'reply' => $reply,
            'intent' => $intent,
            'conversation_id' => $conversation->id,
            'auto_responded' => true,
        ];
    }

    public function replyAsStaff(AiConversation $conversation, string $message): AiMessage
    {
        return AiMessage::create([
            'ai_conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => $message,
            'intent' => 'staff',
        ]);
    }

    public function detectIntent(string $message): string
    {
        $text = strtolower($message);

        if (str_contains($text, 'available') || str_contains($text, 'availability') || str_contains($text, 'free')) {
            return 'availability';
        }
        if (str_contains($text, 'price') || str_contains($text, 'cost') || str_contains($text, 'rate') || str_contains($text, 'how much')) {
            return 'price';
        }
        if (str_contains($text, 'book') || str_contains($text, 'reservation') || str_contains($text, 'reserve')) {
            return 'booking';
        }
        if (str_contains($text, 'pay') || str_contains($text, 'payment') || str_contains($text, 'paid')) {
            return 'payment';
        }

        return 'faq';
    }

    /**
     * @return array<int, string>
     */
    public function factsForIntent(string $intent, string $message): array
    {
        return match ($intent) {
            'availability' => $this->availabilityFacts($message),
            'price' => $this->priceFacts($message),
            'booking' => ['Bookings can only be confirmed through the booking form or by our team. I cannot confirm a reservation in chat.'],
            'payment' => ['Payment status is verified by Stripe. I cannot mark a booking as paid from chat.'],
            default => $this->knowledgeBase->search($message)->map(fn ($article) => $article->title.': '.$article->content)->all(),
        };
    }

    /**
     * @param  array<int, string>  $facts
     */
    public function composeReply(string $message, string $intent, array $facts): string
    {
        $context = implode("\n", $facts);
        $generated = $this->provider->complete(
            $this->provider->instructions(),
            "Guest question: {$message}\nIntent: {$intent}\nFacts:\n{$context}",
        );

        if (is_string($generated) && $generated !== '') {
            return $generated;
        }

        if ($facts === []) {
            return 'I do not have that information yet. Please check the FAQ page or contact us and we will help.';
        }

        return implode("\n", $facts);
    }

    /**
     * @return array<int, string>
     */
    private function availabilityFacts(string $message): array
    {
        [$checkIn, $checkOut] = $this->extractDates($message);
        $property = Property::query()->where('status', 'active')->first();

        if (! $property || ! $checkIn || ! $checkOut) {
            return ['To check availability I need specific dates. You can also use the booking page to search.'];
        }

        $rooms = $this->availability->listAvailableRooms($property->id, $checkIn, $checkOut);

        if ($rooms->isEmpty()) {
            return ['No rooms are available from '.$checkIn->toDateString().' to '.$checkOut->toDateString().'.'];
        }

        return $rooms->map(fn (Room $room) => $room->name.' is available from '.$checkIn->toDateString().' to '.$checkOut->toDateString().'.')->all();
    }

    /**
     * @return array<int, string>
     */
    private function priceFacts(string $message): array
    {
        [$checkIn, $checkOut] = $this->extractDates($message);
        $room = Room::query()->where('status', 'active')->first();

        if (! $room || ! $checkIn || ! $checkOut) {
            return ['I can quote a price once dates are provided. Final prices are always calculated by the booking engine.'];
        }

        $price = $this->pricing->calculateForRange($room, $checkIn, $checkOut);

        return ['The current calculated total for '.$room->name.' is £'.number_format($price['total'], 2).' for '.$price['nights'].' night(s). This is calculated by the pricing engine and may change until booking is confirmed.'];
    }

    /**
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    private function extractDates(string $message): array
    {
        preg_match_all('/\d{4}-\d{2}-\d{2}/', $message, $matches);
        $dates = $matches[0] ?? [];

        if (count($dates) >= 2) {
            return [Carbon::parse($dates[0])->startOfDay(), Carbon::parse($dates[1])->startOfDay()];
        }

        return [null, null];
    }

    public function newSessionId(): string
    {
        return (string) Str::uuid();
    }
}
