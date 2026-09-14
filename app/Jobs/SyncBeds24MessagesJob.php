<?php

namespace App\Jobs;

use App\Jobs\Concerns\TracksCronRun;
use App\Services\Beds24\Beds24MessageService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncBeds24MessagesJob implements ShouldQueue
{
    use Queueable, TracksCronRun;

    public function __construct()
    {
        $this->connection = 'database';
    }

    public int $tries = 3;

    public int $timeout = 600;

    public array $backoff = [30, 120, 300];

    public function handle(Beds24MessageService $sync): void
    {
        $this->trackCronRun(function () use ($sync): void {
            try {
                $summary = $sync->syncAll();
                Log::info('Beds24 message sync complete', $summary);
            } catch (\Throwable $e) {
                Log::error('Beds24 message sync failed', ['message' => $e->getMessage()]);

                throw $e;
            }
        });
    }
}