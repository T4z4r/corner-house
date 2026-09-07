<?php

namespace App\Jobs;

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
                        'created' => $result['created'],
                        'updated' => $result['updated'],
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
