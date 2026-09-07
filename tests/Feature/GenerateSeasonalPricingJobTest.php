<?php

namespace Tests\Feature;

use App\Jobs\GenerateSeasonalPricingJob;
use App\Models\Property;
use App\Models\Setting;
use App\Services\Pricing\SeasonalPricingAutomationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

class GenerateSeasonalPricingJobTest extends TestCase
{
    use RefreshDatabase;

    private function generateResult(): array
    {
        return ['summary' => 'Seasonal pricing generated.', 'created' => 1, 'updated' => 0, 'rules' => new Collection];
    }

    public function test_job_skips_generation_when_disabled(): void
    {
        Property::factory()->create(['status' => 'active']);

        $automation = Mockery::mock(SeasonalPricingAutomationService::class);
        $automation->shouldReceive('generateForProperty')->never();

        (new GenerateSeasonalPricingJob)->handle($automation);

        $this->assertDatabaseCount('pricing_rules', 0);
    }

    public function test_job_generates_rules_for_active_properties_when_enabled(): void
    {
        Setting::query()->create([
            'group' => 'pricing',
            'key' => 'pricing_auto_generate_enabled',
            'value' => '1',
            'type' => 'boolean',
            'label' => 'Auto generate seasonal pricing',
            'cast' => 'boolean',
        ]);

        $activeA = Property::factory()->create(['status' => 'active']);
        $activeB = Property::factory()->create(['status' => 'active']);
        Property::factory()->create(['status' => 'inactive']);

        $handled = [];

        $automation = Mockery::mock(SeasonalPricingAutomationService::class);
        $automation->shouldReceive('generateForProperty')
            ->andReturnUsing(function (Property $property) use (&$handled): array {
                $handled[] = $property->id;

                return $this->generateResult();
            });

        (new GenerateSeasonalPricingJob)->handle($automation);

        $this->assertEqualsCanonicalizing([$activeA->id, $activeB->id], $handled);
    }
}
