<?php

namespace App\Jobs;

use App\Models\PricingRule;
use App\Models\Property;
use App\Models\Setting;
use App\Services\Pricing\SeasonalPricingAutomationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GenerateSeasonalPricingJob implements ShouldQueue
{
    use Queueable;

    public function handle(SeasonalPricingAutomationService $automation): void
    {
        if (! (bool) Setting::getValue('pricing_auto_generate_enabled', false)) {
            return;
        }

        Property::query()
            ->where('status', 'active')
            ->each(function (Property $property) use ($automation): void {
                try {
                    $result = $automation->generateForProperty($property);

                    Log::info('Seasonal pricing auto-generated.', [
                        'property_id' => $property->id,
                        'summary' => $result['summary'] ?? null,
                        'created' => $result['created'],
                        'updated' => $result['updated'],
                        'rules' => collect($result['rules'] ?? [])->map(static fn (PricingRule $rule): array => [
                            'id' => $rule->id,
                            'name' => $rule->name,
                            'rule_type' => $rule->rule_type,
                            'start_date' => $rule->start_date?->toDateString(),
                            'end_date' => $rule->end_date?->toDateString(),
                            'adjustment_type' => $rule->adjustment_type,
                            'adjustment_value' => (float) $rule->adjustment_value,
                            'priority' => $rule->priority,
                        ])->values()->all(),
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('Failed to auto-generate seasonal pricing.', [
                        'property_id' => $property->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            });
    }
}
