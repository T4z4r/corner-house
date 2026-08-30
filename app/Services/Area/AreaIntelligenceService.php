<?php

namespace App\Services\Area;

use App\Models\KnowledgeBaseArticle;
use App\Models\PlacesOfInterest;
use App\Models\Property;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AreaIntelligenceService
{
    /**
     * @return array{
     *     location: array{latitude: float, longitude: float}|null,
     *     updated_at: ?string,
     *     days: array<int, array{date: string, label: string, summary: string, high_c: ?float, low_c: ?float, rain_probability: ?int}>
     * }
     */
    public function weatherForecast(?Property $property, int $days = 7): array
    {
        if (! $property || $property->latitude === null || $property->longitude === null) {
            return [
                'location' => null,
                'updated_at' => null,
                'days' => [],
            ];
        }

        $cacheKey = sprintf('area-weather:%s:%s:%d', $property->latitude, $property->longitude, $days);

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($property, $days): array {
            try {
                $response = Http::connectTimeout(5)
                    ->timeout(15)
                    ->acceptJson()
                    ->get('https://api.open-meteo.com/v1/forecast', [
                        'latitude' => $property->latitude,
                        'longitude' => $property->longitude,
                        'daily' => 'temperature_2m_max,temperature_2m_min,precipitation_probability_max,weathercode',
                        'forecast_days' => $days,
                        'timezone' => 'auto',
                    ]);

                if ($response->failed()) {
                    return [
                        'location' => ['latitude' => (float) $property->latitude, 'longitude' => (float) $property->longitude],
                        'updated_at' => null,
                        'days' => [],
                    ];
                }

                $daily = $response->json('daily', []);
                $dates = (array) ($daily['time'] ?? []);
                $highs = (array) ($daily['temperature_2m_max'] ?? []);
                $lows = (array) ($daily['temperature_2m_min'] ?? []);
                $probabilities = (array) ($daily['precipitation_probability_max'] ?? []);
                $codes = (array) ($daily['weathercode'] ?? []);
                $forecast = [];

                foreach ($dates as $index => $date) {
                    $forecast[] = [
                        'date' => (string) $date,
                        'label' => Carbon::parse((string) $date)->format('D j M'),
                        'summary' => $this->weatherSummary((int) ($codes[$index] ?? 0)),
                        'high_c' => isset($highs[$index]) ? (float) $highs[$index] : null,
                        'low_c' => isset($lows[$index]) ? (float) $lows[$index] : null,
                        'rain_probability' => isset($probabilities[$index]) ? (int) $probabilities[$index] : null,
                    ];
                }

                return [
                    'location' => ['latitude' => (float) $property->latitude, 'longitude' => (float) $property->longitude],
                    'updated_at' => now()->toDateTimeString(),
                    'days' => $forecast,
                ];
            } catch (\Throwable) {
                return [
                    'location' => ['latitude' => (float) $property->latitude, 'longitude' => (float) $property->longitude],
                    'updated_at' => null,
                    'days' => [],
                ];
            }
        });
    }

    /**
     * @return Collection<int, array{title: string, category: string, summary: string, starts_at: ?string, ends_at: ?string}>
     */
    public function nearbyEvents(?Property $property = null, ?Carbon $windowStart = null, ?Carbon $windowEnd = null, int $limit = 4): Collection
    {
        $windowStart ??= now()->startOfMonth();
        $windowEnd ??= now()->endOfMonth();

        $events = KnowledgeBaseArticle::query()
            ->where('status', 'active')
            ->where('show_on_website', true)
            ->whereIn('category', ['event', 'events', 'local-event', 'area-event', 'local-events', 'area-events'])
            ->whereNotNull('starts_at')
            ->whereNotNull('ends_at')
            ->whereDate('starts_at', '<=', $windowEnd->toDateString())
            ->whereDate('ends_at', '>=', $windowStart->toDateString())
            ->orderBy('starts_at')
            ->orderByDesc('priority')
            ->orderBy('title')
            ->limit($limit)
            ->get();

        if ($events->isNotEmpty()) {
            return $events->map(function (KnowledgeBaseArticle $article): array {
                $dateLabel = $article->starts_at?->isSameDay($article->ends_at)
                    ? $article->starts_at?->format('d M Y')
                    : trim(($article->starts_at?->format('d M Y') ?? '').' to '.($article->ends_at?->format('d M Y') ?? ''));

                return [
                    'title' => $article->title,
                    'category' => Str::headline($article->category),
                    'summary' => $this->shortSummary($article->content),
                    'starts_at' => $dateLabel ?: null,
                    'ends_at' => $article->ends_at?->toDateString(),
                ];
            });
        }

        return PlacesOfInterest::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->limit($limit)
            ->get()
            ->map(function (PlacesOfInterest $place) use ($property): array {
                return [
                    'title' => $place->name,
                    'category' => Str::headline($place->category),
                    'summary' => trim((string) ($place->description ?: ($property?->city ? 'Popular stop near '.$property->city : 'Recommended local stop'))),
                    'starts_at' => null,
                    'ends_at' => null,
                ];
            });
    }

    /**
     * @return array<int, string>
     */
    public function weatherFacts(?Property $property): array
    {
        $forecast = $this->weatherForecast($property);

        if ($forecast['days'] === []) {
            return ['I could not load a weather forecast for this property right now.'];
        }

        return collect($forecast['days'])
            ->take(3)
            ->map(function (array $day): string {
                $parts = [$day['label'].': '.$day['summary']];

                if ($day['high_c'] !== null && $day['low_c'] !== null) {
                    $parts[] = 'high '.$this->formatTemperature($day['high_c']).'C / low '.$this->formatTemperature($day['low_c']).'C';
                }

                if ($day['rain_probability'] !== null) {
                    $parts[] = $day['rain_probability'].'% rain chance';
                }

                return implode(' · ', $parts);
            })
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function eventFacts(?Property $property = null, ?Carbon $windowStart = null, ?Carbon $windowEnd = null): array
    {
        $events = $this->nearbyEvents($property, $windowStart, $windowEnd);

        if ($events->isEmpty()) {
            return ['I could not find any curated local events or nearby highlights right now.'];
        }

        return $events
            ->take(4)
            ->map(fn (array $event): string => $event['title'].' - '.$event['summary'])
            ->all();
    }

    private function weatherSummary(int $code): string
    {
        return match ($code) {
            0 => 'Clear sky',
            1 => 'Mainly clear',
            2 => 'Partly cloudy',
            3 => 'Overcast',
            45, 48 => 'Foggy',
            51, 53, 55, 56, 57 => 'Drizzle',
            61, 63, 65, 66, 67 => 'Rain',
            71, 73, 75, 77 => 'Snow',
            80, 81, 82 => 'Rain showers',
            85, 86 => 'Snow showers',
            95, 96, 99 => 'Thunderstorm',
            default => 'Mixed conditions',
        };
    }

    private function shortSummary(string $content): string
    {
        return Str::of(trim($content))
            ->replaceMatches('/\s+/', ' ')
            ->limit(140)
            ->toString();
    }

    private function formatTemperature(float $value): string
    {
        return number_format($value, 0);
    }
}
