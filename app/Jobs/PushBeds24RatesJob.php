<?php

namespace App\Jobs;

use App\Models\ChannelMapping;
use App\Models\Room;
use App\Services\Channel\ChannelManager;
use App\Services\Pricing\PricingEngine;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class PushBeds24RatesJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function handle(ChannelManager $channels, PricingEngine $pricing): void
    {
        $from = Carbon::today();
        $to = $from->copy()->addDays(90);

        ChannelMapping::query()
            ->where('status', 'active')
            ->with(['account', 'room'])
            ->each(function (ChannelMapping $mapping) use ($channels, $pricing, $from, $to): void {
                $account = $mapping->account;
                $room = $mapping->room;

                if (! $account || $account->status !== 'active' || ! $room instanceof Room) {
                    return;
                }

                try {
                    $quote = $pricing->calculateForRange($room, $from, $to);
                    $channels->provider($account->provider)->pushRates($account, [[
                        'roomId' => $mapping->external_room_id,
                        'from' => $from->toDateString(),
                        'to' => $to->toDateString(),
                        'price' => $quote['per_night'][$from->toDateString()] ?? $room->base_rate,
                        'minimumStay' => $quote['minimum_stay'],
                    ]]);
                } catch (\Throwable $e) {
                    Log::warning('Failed to push channel rates', [
                        'mapping_id' => $mapping->id,
                        'message' => $e->getMessage(),
                    ]);
                }
            });
    }
}
