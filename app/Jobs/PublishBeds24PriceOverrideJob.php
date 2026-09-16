<?php

namespace App\Jobs;

use App\Models\PricingOverride;
use App\Services\Beds24\Beds24PricingPublisher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class PublishBeds24PriceOverrideJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(public int $overrideId) {}

    /**
     * Execute the job.
     */
    public function handle(Beds24PricingPublisher $publisher): void
    {
        $override = PricingOverride::query()->find($this->overrideId);
        if (! $override || ! $override->is_enabled) {
            return;
        }

        if (! $publisher->postOverride($override)) {
            throw new \RuntimeException('Price override could not be published to Beds24. Check the account, room mapping and Beds24 logs.');
        }
    }
}
