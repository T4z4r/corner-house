<?php

namespace App\Jobs;

use App\Models\ChannelAccount;
use App\Services\Beds24\Beds24SyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncBeds24BookingsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function handle(Beds24SyncService $sync): void
    {
        ChannelAccount::query()
            ->where('provider', 'beds24')
            ->where('status', 'active')
            ->each(function (ChannelAccount $account) use ($sync): void {
                try {
                    $sync->synchronize($account);
                } catch (\Throwable $e) {
                    $account->update(['status' => 'error', 'last_error' => $e->getMessage()]);
                    Log::error('Beds24 sync failed', ['account_id' => $account->id, 'message' => $e->getMessage()]);
                }
            });
    }
}
