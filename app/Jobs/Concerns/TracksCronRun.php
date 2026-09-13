<?php

namespace App\Jobs\Concerns;

use App\Services\Cron\CronRunRecorder;

/**
 * Records a successful/failed run row for scheduled jobs so the admin Cron
 * Jobs page can show which background tasks have run and their outcome.
 */
trait TracksCronRun
{
    protected function trackCronRun(callable $callback): void
    {
        CronRunRecorder::track(class_basename(static::class), $callback);
    }
}