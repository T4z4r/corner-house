<?php

namespace App\Jobs;

use App\Jobs\Concerns\TracksCronRun;
use App\Models\ChannelAccount;
use App\Models\ChannelSyncLog;
use App\Services\Beds24\Beds24AlertService;
use App\Services\Beds24\Beds24SyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncBeds24BookingsJob implements ShouldQueue
{
    use Queueable, TracksCronRun;

    public function __construct()
    {
        $this->connection = 'sync';
    }

    public int $tries = 3;

    public function handle(Beds24SyncService $sync, Beds24AlertService $alerts): void
    {
        $this->trackCronRun(function () use ($sync, $alerts): void {
            ChannelAccount::query()
                ->where('provider', 'beds24')
                ->each(function (ChannelAccount $account) use ($sync, $alerts): void {
                    if (! $account->isSyncEligible()) {
                        ChannelSyncLog::create([
                            'channel_account_id' => $account->id,
                            'channel' => $account->provider,
                            'operation' => 'full_sync',
                            'status' => 'failed',
                            'error_message' => 'This Beds24 account has no valid credentials. Enter an invitation code (or refresh token) on the Integrations page to reconnect.',
                            'started_at' => now(),
                            'completed_at' => now(),
                        ]);

                        return;
                    }

                    $log = ChannelSyncLog::create([
                        'channel_account_id' => $account->id,
                        'channel' => $account->provider,
                        'operation' => 'full_sync',
                        'status' => 'pending',
                        'started_at' => now(),
                    ]);

                    try {
                        $summary = $sync->synchronize($account, $log->id);

                        $log->update([
                            'status' => $summary['errors'] !== [] ? 'failed' : 'success',
                            'error_message' => $summary['errors'] !== [] ? implode('; ', $summary['errors']) : null,
                            'completed_at' => now(),
                        ]);

                        Log::info('Beds24 sync complete', [
                            'account_id' => $account->id,
                            'provider' => $account->provider,
                            'properties' => $summary['properties'],
                            'rooms' => $summary['rooms'],
                            'bookings' => $summary['bookings'],
                            'bookings_pushed' => $summary['bookings_pushed'],
                            'overrides' => $summary['overrides'],
                            'blocks' => $summary['blocks'],
                            'errors' => $summary['errors'],
                        ]);

                        if ($summary['errors'] !== []) {
                            $alerts->notifyFailure($account, $summary['errors']);
                        }
                    } catch (\Throwable $e) {
                        $log->update([
                            'status' => 'failed',
                            'error_message' => $e->getMessage(),
                            'completed_at' => now(),
                        ]);
                        $account->update(['status' => 'error', 'last_error' => $e->getMessage()]);
                        Log::error('Beds24 sync failed', ['account_id' => $account->id, 'message' => $e->getMessage()]);

                        $alerts->notifyFailure($account, [$e->getMessage()]);
                    }
                });
        });
    }
}
