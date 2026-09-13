<?php

namespace App\Jobs;

use App\Models\ChannelAccount;
use App\Services\Beds24\Beds24AlertService;
use App\Services\Beds24\Beds24SyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncBeds24BookingsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function handle(Beds24SyncService $sync, Beds24AlertService $alerts): void
    {
        ChannelAccount::query()
            ->where('provider', 'beds24')
            ->each(function (ChannelAccount $account) use ($sync, $alerts): void {
                if (! $account->isSyncEligible()) {
                    return;
                }

                try {
                    $summary = $sync->synchronize($account);

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
                    $account->update(['status' => 'error', 'last_error' => $e->getMessage()]);
                    Log::error('Beds24 sync failed', ['account_id' => $account->id, 'message' => $e->getMessage()]);

                    $alerts->notifyFailure($account, [$e->getMessage()]);
                }
            });
    }
}
