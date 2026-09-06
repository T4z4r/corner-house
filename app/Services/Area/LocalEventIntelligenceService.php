<?php

namespace App\Services\Area;

use App\Models\KnowledgeBaseArticle;
use App\Models\Property;
use App\Services\AI\AiProviderService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class LocalEventIntelligenceService
{
    public function __construct(private readonly AiProviderService $provider) {}

    /**
     * Ask the AI provider for a guest-facing calendar of bank holidays and
     * local events near the property, then persist each result as an event
     * knowledge-base article (category "event"). Reruns are idempotent via
     * the ai_generation_key on each article.
     *
     * @return array{
     *     summary: string,
     *     created: int,
     *     updated: int,
     *     events: Collection<int, KnowledgeBaseArticle>
     * }
     */
    public function generateForProperty(Property $property, ?int $userId = null): array
    {
        $payload = $this->provider->generateJson(
            'You curate an events and bank-holiday calendar for a holiday home. Return valid JSON matching the schema. Include only real UK bank holidays, school holidays, and well-known annual local events and festivals near the property within the requested window. Prefer events guests would plan a short break around, within an hour\'s drive. Keep titles short, descriptions to two sentences in British English, and use accurate future dates.',
            json_encode($this->buildContext($property), JSON_THROW_ON_ERROR),
            $this->schema(),
            'LocalEventCalendar',
        );

        if (! is_array($payload) || ! isset($payload['events']) || ! is_array($payload['events'])) {
            return $this->fallbackEvents($property, $userId);
        }

        $created = 0;
        $updated = 0;
        $events = collect();

        foreach ($payload['events'] as $eventData) {
            if (! is_array($eventData)) {
                continue;
            }

            $dates = $this->normalizeDates($eventData['start_date'] ?? null, $eventData['end_date'] ?? null);

            if (! $dates) {
                continue;
            }

            $title = Str::limit(trim((string) ($eventData['title'] ?? '')), 255, '');

            if ($title === '') {
                continue;
            }

            $generationKey = Str::slug((string) ($eventData['generation_key'] ?? $title.'-'.$dates['start_date']));

            if ($generationKey === '') {
                continue;
            }

            $existing = KnowledgeBaseArticle::query()->where('ai_generation_key', $generationKey)->first();

            $article = KnowledgeBaseArticle::updateOrCreate(
                ['ai_generation_key' => $generationKey],
                [
                    'category' => 'event',
                    'title' => $title,
                    'content' => Str::limit(trim((string) ($eventData['description'] ?? '')), 5000, ''),
                    'status' => 'active',
                    'priority' => (int) ($eventData['priority'] ?? 1),
                    'starts_at' => $dates['start_date'],
                    'ends_at' => $dates['end_date'],
                    'source' => 'ai',
                    'show_on_website' => (bool) ($eventData['show_on_website'] ?? true),
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ],
            );

            if ($existing) {
                $updated++;
            } else {
                $created++;
            }

            $events->push($article);
        }

        return [
            'summary' => (string) ($payload['summary'] ?? 'Local holidays and events generated.'),
            'created' => $created,
            'updated' => $updated,
            'events' => $events,
        ];
    }

    /**
     * Deterministic standard UK bank holidays for the window, used when the
     * AI provider cannot return a structured list.
     *
     * @return array{summary: string, created: int, updated: int, events: Collection<int, KnowledgeBaseArticle>}
     */
    private function fallbackEvents(Property $property, ?int $userId): array
    {
        $start = now()->startOfMonth();
        $end = now()->addMonths(12)->endOfMonth();
        $year = $end->year;

        $entries = array_filter([
            $this->holiday('New Year\'s Day', Carbon::create($year, 1, 1), 'The January bank holiday.'),
            $this->easterEntry($start, $end),
            $this->holiday('Early May bank holiday', $this->firstMondayOf($year, 5), 'The first May bank holiday.'),
            $this->holiday('Spring bank holiday', $this->lastMondayOf($year, 5), 'The late May bank holiday.'),
            $this->holiday('Summer bank holiday', $this->lastMondayOf($year, 8), 'The August bank holiday.'),
            $this->holiday('Christmas Day', Carbon::create($year - 1, 12, 25), 'Christmas Day.'),
            $this->holiday('Boxing Day', Carbon::create($year - 1, 12, 26), 'Boxing Day bank holiday.'),
        ]);

        return $this->persistEntries($entries, $userId, 'The AI assistant was unavailable; added standard UK bank holidays for the coming year instead.');
    }

    /**
     * @param  array<int, array{0: string, 1: string, 2: string, 3: string, 4: string}>  $entries
     * @return array{summary: string, created: int, updated: int, events: Collection<int, KnowledgeBaseArticle>}
     */
    private function persistEntries(array $entries, ?int $userId, string $summary): array
    {
        $created = 0;
        $updated = 0;
        $events = collect();

        foreach ($entries as [$key, $title, $content, $startDate, $endDate]) {
            $existing = KnowledgeBaseArticle::query()->where('ai_generation_key', $key)->first();

            $article = KnowledgeBaseArticle::updateOrCreate(
                ['ai_generation_key' => $key],
                [
                    'category' => 'event',
                    'title' => $title,
                    'content' => $content,
                    'status' => 'active',
                    'priority' => 1,
                    'starts_at' => $startDate,
                    'ends_at' => $endDate,
                    'source' => 'ai',
                    'show_on_website' => true,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ],
            );

            if ($existing) {
                $updated++;
            } else {
                $created++;
            }

            $events->push($article);
        }

        if ($created === 0 && $updated === 0 && $events->isEmpty()) {
            return [
                'summary' => 'The AI assistant could not provide an event list and no standard holidays fall in the window.',
                'created' => 0,
                'updated' => 0,
                'events' => $events,
            ];
        }

        return [
            'summary' => $summary,
            'created' => $created,
            'updated' => $updated,
            'events' => $events,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildContext(Property $property): array
    {
        $start = now()->startOfMonth();
        $end = now()->addMonths(12)->endOfMonth();

        return [
            'current_date' => now()->toDateString(),
            'property' => [
                'name' => $property->name,
                'city' => $property->city,
                'postcode' => $property->postcode,
                'latitude' => $property->latitude,
                'longitude' => $property->longitude,
            ],
            'window' => [
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
            ],
            'existing_events' => KnowledgeBaseArticle::query()
                ->where('category', 'event')
                ->whereNotNull('starts_at')
                ->orderBy('starts_at')
                ->get(['title', 'starts_at', 'ends_at'])
                ->map(fn (KnowledgeBaseArticle $article): array => [
                    'title' => $article->title,
                    'start' => $article->starts_at?->toDateString(),
                    'end' => $article->ends_at?->toDateString(),
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'summary' => ['type' => 'string'],
                'events' => [
                    'type' => 'array',
                    'minItems' => 1,
                    'maxItems' => 20,
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'generation_key' => ['type' => 'string'],
                            'title' => ['type' => 'string'],
                            'category' => ['type' => 'string', 'enum' => ['holiday', 'festival', 'event', 'market', 'show', 'sport']],
                            'location' => ['type' => 'string'],
                            'description' => ['type' => 'string'],
                            'start_date' => ['type' => 'string'],
                            'end_date' => ['type' => 'string'],
                            'priority' => ['type' => 'integer'],
                            'show_on_website' => ['type' => 'boolean'],
                        ],
                        'required' => ['generation_key', 'title', 'category', 'location', 'description', 'start_date', 'end_date', 'priority', 'show_on_website'],
                        'additionalProperties' => false,
                    ],
                ],
            ],
            'required' => ['summary', 'events'],
            'additionalProperties' => false,
        ];
    }

    /**
     * @return array{start_date: string, end_date: string}|null
     */
    private function normalizeDates(mixed $startDate, mixed $endDate): ?array
    {
        if (! is_string($startDate) || ! is_string($endDate) || $startDate === '' || $endDate === '') {
            return null;
        }

        try {
            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->startOfDay();
        } catch (\Throwable) {
            return null;
        }

        if ($end->lt($start)) {
            return null;
        }

        return [
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
        ];
    }

    /**
     * @return array{0: string, 1: string, 2: string, 3: string, 4: string}|null
     */
    private function holiday(string $title, ?Carbon $date, string $content, int $priority = 1): ?array
    {
        if (! $date) {
            return null;
        }

        return [
            Str::slug($title.'-'.$date->toDateString()),
            $title,
            $content,
            $date->toDateString(),
            $date->toDateString(),
        ];
    }

    private function firstMondayOf(int $year, int $month): ?Carbon
    {
        $candidate = Carbon::create($year, $month, 1)->startOfDay();

        while ($candidate->dayOfWeek !== Carbon::MONDAY) {
            $candidate->addDay();
        }

        return $candidate;
    }

    private function lastMondayOf(int $year, int $month): ?Carbon
    {
        $candidate = Carbon::create($year, $month, 1)->endOfMonth()->startOfDay();

        while ($candidate->dayOfWeek !== Carbon::MONDAY) {
            $candidate->subDay();
        }

        return $candidate;
    }

    /**
     * Easter Friday through Easter weekend for the first year of the window,
     * via the anonymous Gregorian computus, when it falls inside the window.
     *
     * @return array{0: string, 1: string, 2: string, 3: string, 4: string}|null
     */
    private function easterEntry(Carbon $windowStart, Carbon $windowEnd): ?array
    {
        $easterSunday = $this->easterSunday($windowEnd->year);
        $goodFriday = $easterSunday->copy()->subDays(2);

        if (! $goodFriday->between($windowStart, $windowEnd, true)) {
            return null;
        }

        return [
            'good-friday-and-easter-'.$easterSunday->year,
            'Easter weekend',
            'The Easter weekend, including Good Friday and the Easter Monday bank holiday.',
            $goodFriday->toDateString(),
            $easterSunday->toDateString(),
        ];
    }

    private function easterSunday(int $year): Carbon
    {
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $month = intdiv($h + $l - 7 * $m + 114, 31);
        $day = (($h + $l - 7 * $m + 114) % 31) + 1;

        return Carbon::create($year, $month, $day)->startOfDay();
    }
}
