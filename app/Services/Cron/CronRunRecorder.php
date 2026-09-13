<?php

namespace App\Services\Cron;

use App\Models\CronJobRun;

class CronRunRecorder
{
    public const STATUS_RUNNING = 'running';

    public const STATUS_SUCCESS = 'success';

    public const STATUS_FAILED = 'failed';

    /**
     * Record the start of a scheduled job run.
     */
    public static function start(string $job): CronJobRun
    {
        return CronJobRun::create([
            'job' => $job,
            'status' => self::STATUS_RUNNING,
            'started_at' => now(),
        ]);
    }

    /**
     * Mark a previously started run as finished. A null error means success.
     */
    public static function finish(CronJobRun $run, ?string $error = null): void
    {
        $finishedAt = now();

        $run->update([
            'status' => $error === null ? self::STATUS_SUCCESS : self::STATUS_FAILED,
            'finished_at' => $finishedAt,
            'duration_ms' => max(0, (int) $run->started_at->diffInMilliseconds($finishedAt)),
            'error' => $error,
        ]);
    }

    /**
     * Run a callable while recording its outcome. Failures are recorded and
     * then re-thrown so the original caller (queue worker / scheduler) still
     * sees the exception.
     */
    public static function track(string $job, callable $callback): void
    {
        $run = self::start($job);

        try {
            $callback();
            self::finish($run);
        } catch (\Throwable $e) {
            self::finish($run, $e->getMessage());
            throw $e;
        }
    }
}