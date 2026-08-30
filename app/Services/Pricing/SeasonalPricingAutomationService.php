<?php

namespace App\Services\Pricing;

use App\Models\PricingRule;
use App\Models\Property;
use App\Services\AI\AiProviderService;
use App\Services\Area\AreaIntelligenceService;
use App\Services\Revenue\RevenueAnalyticsService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SeasonalPricingAutomationService
{
    public function __construct(
        private readonly AiProviderService $provider,
        private readonly AreaIntelligenceService $areaIntelligence,
        private readonly RevenueAnalyticsService $revenueAnalytics,
    ) {}

    /**
     * @return array{
     *     summary: string,
     *     created: int,
     *     updated: int,
     *     rules: Collection<int, PricingRule>
     * }
     */
    public function generateForProperty(Property $property): array
    {
        $context = $this->buildContext($property);
        $payload = $this->provider->generateJson(
            'You generate conservative seasonal pricing rules for a hospitality business. Return only valid JSON that matches the schema. Use future dates only, keep recommendations realistic, and prefer property-level seasonal rules unless a room-level rule is clearly warranted.',
            json_encode($context, JSON_THROW_ON_ERROR),
            [
                'type' => 'object',
                'properties' => [
                    'summary' => ['type' => 'string'],
                    'rules' => [
                        'type' => 'array',
                        'minItems' => 1,
                        'maxItems' => 6,
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'generation_key' => ['type' => 'string'],
                                'name' => ['type' => 'string'],
                                'rule_type' => ['type' => 'string', 'enum' => ['seasonal', 'event', 'holiday']],
                                'start_date' => ['type' => 'string'],
                                'end_date' => ['type' => 'string'],
                                'priority' => ['type' => 'integer'],
                                'adjustment_type' => ['type' => 'string', 'enum' => ['percent', 'amount', 'multiplier']],
                                'adjustment_value' => ['type' => 'number'],
                                'minimum_stay' => ['type' => ['integer', 'null']],
                                'max_stay' => ['type' => ['integer', 'null']],
                                'occupancy_threshold' => ['type' => ['number', 'null']],
                                'days_before_checkin' => ['type' => ['integer', 'null']],
                                'apply_weekends_only' => ['type' => 'boolean'],
                                'reasoning' => ['type' => 'string'],
                            ],
                            'required' => ['generation_key', 'name', 'rule_type', 'start_date', 'end_date', 'priority', 'adjustment_type', 'adjustment_value', 'apply_weekends_only', 'reasoning'],
                            'additionalProperties' => false,
                        ],
                    ],
                ],
                'required' => ['summary', 'rules'],
                'additionalProperties' => false,
            ],
            'SeasonalPricingPlan',
        );

        if (! is_array($payload) || ! isset($payload['rules']) || ! is_array($payload['rules'])) {
            $payload = $this->fallbackPlan($property);
        }

        $created = 0;
        $updated = 0;
        $rules = collect();

        foreach ($payload['rules'] as $ruleData) {
            if (! is_array($ruleData)) {
                continue;
            }

            $generationKey = Str::slug((string) ($ruleData['generation_key'] ?? $ruleData['name'] ?? Str::uuid()));
            $dates = $this->normalizeDates($ruleData['start_date'] ?? null, $ruleData['end_date'] ?? null);

            if (! $dates) {
                continue;
            }

            $attributes = [
                'property_id' => $property->id,
                'room_id' => null,
                'ai_generation_key' => $generationKey,
            ];

            $existing = PricingRule::query()->where($attributes)->first();

            $rule = PricingRule::updateOrCreate($attributes, [
                'name' => (string) $ruleData['name'],
                'rule_type' => (string) $ruleData['rule_type'],
                'start_date' => $dates['start_date'],
                'end_date' => $dates['end_date'],
                'priority' => (int) ($ruleData['priority'] ?? 5),
                'adjustment_type' => (string) $ruleData['adjustment_type'],
                'adjustment_value' => (float) ($ruleData['adjustment_value'] ?? 0),
                'minimum_stay' => $ruleData['minimum_stay'] ?? null,
                'max_stay' => $ruleData['max_stay'] ?? null,
                'occupancy_threshold' => $ruleData['occupancy_threshold'] ?? null,
                'days_before_checkin' => $ruleData['days_before_checkin'] ?? null,
                'apply_weekends_only' => (bool) ($ruleData['apply_weekends_only'] ?? false),
                'is_enabled' => true,
                'generated_by_ai' => true,
                'generated_at' => now(),
                'generation_metadata' => [
                    'summary' => (string) ($payload['summary'] ?? ''),
                    'reasoning' => (string) ($ruleData['reasoning'] ?? ''),
                    'source' => 'ai',
                    'property' => $property->name,
                ],
            ]);

            if ($existing) {
                $updated++;
            } else {
                $created++;
            }

            $rules->push($rule);
        }

        return [
            'summary' => (string) ($payload['summary'] ?? 'Seasonal pricing rules generated.'),
            'created' => $created,
            'updated' => $updated,
            'rules' => $rules,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildContext(Property $property): array
    {
        $analytics = $this->revenueAnalytics->dashboardStats($property->id);
        $weather = $this->areaIntelligence->weatherForecast($property);

        return [
            'current_date' => now()->toDateString(),
            'property' => [
                'id' => $property->id,
                'name' => $property->name,
                'city' => $property->city,
                'postcode' => $property->postcode,
                'rooms' => $property->rooms()->where('status', 'active')->count(),
            ],
            'performance' => $analytics,
            'weather_forecast' => $weather['days'],
            'nearby_events' => $this->areaIntelligence->nearbyEvents($property)->all(),
            'season_window' => [
                'start' => now()->startOfMonth()->toDateString(),
                'end' => now()->addMonths(6)->endOfMonth()->toDateString(),
            ],
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
     * @return array{summary: string, rules: array<int, array<string, mixed>>}
     */
    private function fallbackPlan(Property $property): array
    {
        $start = now()->startOfMonth()->addWeeks(2);
        $end = now()->addMonths(3)->endOfMonth();

        return [
            'summary' => 'Generated a conservative AI fallback plan because the model did not return structured pricing rules.',
            'rules' => [
                [
                    'generation_key' => Str::slug($property->slug.'-'.$start->format('Y-m').'-seasonal'),
                    'name' => 'Seasonal uplift',
                    'rule_type' => 'seasonal',
                    'start_date' => $start->toDateString(),
                    'end_date' => $end->toDateString(),
                    'priority' => 5,
                    'adjustment_type' => 'percent',
                    'adjustment_value' => 12,
                    'minimum_stay' => 2,
                    'max_stay' => null,
                    'occupancy_threshold' => null,
                    'days_before_checkin' => null,
                    'apply_weekends_only' => false,
                    'reasoning' => 'Fallback rule to reflect general seasonal demand.',
                ],
            ],
        ];
    }
}
