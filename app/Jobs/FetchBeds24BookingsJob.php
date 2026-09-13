<?php

namespace App\Jobs;

use App\Models\ChannelAccount;
use App\Services\Channel\ChannelManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class FetchBeds24BookingsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function handle(ChannelManager $channels): void
    {
        ChannelAccount::query()
            ->where('provider', 'beds24')
            ->where('status', 'active')
            ->each(function (ChannelAccount $account) use ($channels): void {
                try {
                    $channels->syncBookings($account, true);
                } catch (\Throwable $e) {
                    $account->update(['status' => 'error', 'last_error' => $e->getMessage()]);
                    Log::error('Beds24 booking fetch failed', ['account_id' => $account->id, 'message' => $e->getMessage()]);
                }
            });
    }
}
