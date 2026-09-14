<?php

namespace App\Jobs;

use App\Jobs\Concerns\TracksCronRun;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Disabled: automated Beds24 rate pushes are blocked. The scheduled cron,
 * the manual channel sync and the Cron Jobs page no longer trigger this job,
 * and any instance still queued from before is a harmless no-op so prices are
 * never overwritten from sync or cron. Prices are only changed via the manual
 * Publishing actions on the Integrations page.
 */
class PushBeds24RatesJob implements ShouldQueue
{
    use Queueable, TracksCronRun;

    public function __construct()
    {
        $this->connection = 'database';
    }

    public int $tries = 3;

    public int $timeout = 600;

    public array $backoff = [30, 120, 300];

    public function handle(): void
    {
        $this->trackCronRun(function (): void {
            Log::info('PushBeds24RatesJob skipped: automated Beds24 rate pushes are disabled.');
        });
    }
}