<?php

namespace App\Services\AI;

use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\Property;
use App\Models\Room;
use App\Services\Area\AreaIntelligenceService;
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
        private readonly AreaIntelligenceService $areaIntelligence,
        private readonly AiProviderService $provider,
        private readonly TokenOptimizationService $tokenOptimiser,
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
                'reply' => 'Thanks - a member of the team will reply to you shortly.',
                'intent' => $intent,
                'conversation_id' => $conversation->id,
                'auto_responded' => false,
            ];
        }

        $facts = $this->factsForIntent($intent, $message);
        $reply = $this->composeReply($message, $intent, $facts, $conversation);

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
        $hasWeather = str_contains($text, 'weather')
            || str_contains($text, 'forecast')
            || str_contains($text, 'rain')
            || str_contains($text, 'temperature')
            || str_contains($text, 'hot')
            || str_contains($text, 'cold');
        $hasEvent = str_contains($text, 'event')
            || str_contains($text, 'events')
            || str_contains($text, 'nearby')
            || str_contains($text, 'what is on')
            || str_contains($text, "what's on")
            || str_contains($text, 'what is happening')
            || str_contains($text, 'around here')
            || str_contains($text, 'local area');

        if ($hasWeather && $hasEvent) {
            return 'area';
        }
        if ($hasWeather) {
            return 'weather';
        }
        if ($hasEvent) {
            return 'event';
        }

        if ($this->matchesProperty($text)) {
            return 'property';
        }
        if ($this->matchesRooms($text)) {
            return 'rooms';
        }

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
            'property' => $this->propertyFacts($message),
            'rooms' => $this->roomsFacts(),
            'weather' => $this->areaIntelligence->weatherFacts($this->activeProperty()),
            'event' => $this->areaIntelligence->eventFacts($this->activeProperty()),
            'area' => array_merge(
                $this->areaIntelligence->weatherFacts($this->activeProperty()),
                $this->areaIntelligence->eventFacts($this->activeProperty()),
            ),
            'booking' => ['Bookings can only be confirmed through the booking form or by our team. I cannot confirm a reservation in chat.'],
            'payment' => ['Payment status is verified by Stripe. I cannot mark a booking as paid from chat.'],
            default => $this->knowledgeBase->search($message)
                ->map(fn ($article) => $article->title.': '.$this->knowledgeBase->truncatedContent($article))
                ->all(),
        };
    }

    /**
     * @param  array<int, string>  $facts
     */
    public function composeReply(string $message, string $intent, array $facts, ?AiConversation $conversation = null): string
    {
        $history = $conversation
            ? $this->tokenOptimiser->buildOptimisedHistory($conversation, $message)
            : [];

        $messages = $this->tokenOptimiser->buildPrompt(
            $this->provider->instructions(),
            $message,
            $intent,
            $facts,
            $history,
        );

        $generated = $this->provider->completeMessages($messages);

        if (is_string($generated) && $generated !== '') {
            return $generated;
        }

        if ($facts === []) {
            return 'I do not have that information yet. Please check the FAQ page or contact us and we will help.';
        }

        $optimised = $this->tokenOptimiser->optimiseFacts($facts, $intent);

        return implode("\n", $optimised);
    }

    /**
     * @return array<int, string>
     */
    private function availabilityFacts(string $message): array
    {
        [$checkIn, $checkOut] = $this->extractDates($message);
        $property = $this->activeProperty();

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
     * @return array<int, string>
     */
    private function propertyFacts(string $message): array
    {
        $property = $this->activeProperty();

        if (! $property) {
            return ['No property details are set up yet. Please contact us for more information.'];
        }

        $location = collect([$property->city, $property->postcode, $property->country])
            ->filter()
            ->implode(', ');

        $facts = [];

        $facts[] = 'Accommodation: '.($property->name ?: 'Corner House').'.';

        if ($property->short_description) {
            $facts[] = $property->short_description;
        } elseif ($property->description) {
            $facts[] = Str::limit($property->description, 280);
        }

        if ($location !== '') {
            $facts[] = 'Location: '.$location.'.';
        }

        $facts[] = 'Sleeps up to '.($property->capacity ?: $property->bedrooms ?: 'N/A').' guest(s); '.($property->bedrooms ?? 0).' bedroom(s), '.($property->bathrooms ?? 0).' bathroom(s).';

        $facts[] = 'Check-in '.($property->check_in_from ?? '15:00').'-'.($property->check_in_until ?? '18:00').', check-out '.($property->check_out_from ?? '08:00').'-'.($property->check_out_until ?? '12:00').'.';

        $amenities = $property->amenities()
            ->where('amenities.is_active', true)
            ->orderBy('name')
            ->pluck('name')
            ->all();

        if ($amenities !== []) {
            $facts[] = 'Amenities include: '.implode(', ', array_slice($amenities, 0, 12)).(count($amenities) > 12 ? ' and more.' : '.');
        }

        if (str_contains(strtolower($message), 'animal') || str_contains(strtolower($message), 'pet')) {
            $facts[] = 'Pets: '.($property->pets_allowed ?: 'no').'.';
        }
        if (str_contains(strtolower($message), 'smok')) {
            $facts[] = 'Smoking: '.($property->smoking_allowed ? 'allowed' : 'not allowed').'.';
        }

        return $facts;
    }

    /**
     * @return array<int, string>
     */
    private function roomsFacts(): array
    {
        $property = $this->activeProperty();

        if (! $property) {
            return ['No rooms are set up yet. Please contact us for more information.'];
        }

        $rooms = $property->rooms()
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        if ($rooms->isEmpty()) {
            return ['There are currently no bookable rooms listed. Please contact us for availability.'];
        }

        return $rooms->map(function (Room $room): string {
            $sleeps = $room->sleeps ?: $room->capacity;
            $parts = [
                $room->name,
                $room->type ? ' ('.ucfirst($room->type).')' : '',
                ' - sleeps '.($sleeps ?: 'N/A'),
                ' · '.($room->bedrooms ?? 0).' bed(s), '.($room->bathrooms ?? 0).' bathroom(s)',
                ' · from £'.number_format((float) $room->base_rate, 2).'/night',
            ];

            return implode('', $parts);
        })->values()->all();
    }

    private function matchesProperty(string $text): bool
    {
        $patterns = [
            'about the property', 'about your property', 'about the house', 'about the accommodation',
            'the property like', 'the house like', 'accommodation like',
            'where are you', 'where is the property', 'your address', 'your location', 'where do you',
            'amenities', 'what is included', 'whats included',
            'check in time', 'check-in time', 'check out time', 'check-out time', 'what time is check',
            'is there a garden', 'is there wifi', 'is there wi-fi', 'is there parking', 'is there a pool',
        ];

        foreach ($patterns as $pattern) {
            if (str_contains($text, $pattern)) {
                return true;
            }
        }

        return false;
    }

    private function matchesRooms(string $text): bool
    {
        $patterns = [
            'what rooms', 'which rooms', 'types of room', 'room options', 'tell me about the rooms',
            'about the rooms', 'your rooms', 'do you have rooms', 'what room',
            'bedrooms', 'bedroom', 'sleep', 'capacity', 'how many guests', 'how many people',
            'is there a room', 'room with',
        ];

        foreach ($patterns as $pattern) {
            if (str_contains($text, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    private function extractDates(string $message): array
    {
        $text = strtolower($message);
        preg_match_all('/\d{4}-\d{2}-\d{2}/', $message, $matches);
        $dates = $matches[0] ?? [];

        if (count($dates) >= 2) {
            return [Carbon::parse($dates[0])->startOfDay(), Carbon::parse($dates[1])->startOfDay()];
        }

        if (count($dates) === 1) {
            $start = Carbon::parse($dates[0])->startOfDay();

            return [$start, $start->copy()->addDay()];
        }

        if (str_contains($text, 'this weekend') || str_contains($text, 'the weekend')) {
            $saturday = Carbon::now()->next(Carbon::SATURDAY);

            return [$saturday, $saturday->copy()->addDays(2)];
        }

        if (str_contains($text, 'tonight') || str_contains($text, 'today')) {
            return [Carbon::today(), Carbon::tomorrow()];
        }

        if (str_contains($text, 'tomorrow')) {
            return [Carbon::tomorrow(), Carbon::now()->startOfDay()->addDays(2)];
        }

        return [null, null];
    }

    private function activeProperty(): ?Property
    {
        return Property::query()->where('status', 'active')->first();
    }

    public function newSessionId(): string
    {
        return (string) Str::uuid();
    }
}
