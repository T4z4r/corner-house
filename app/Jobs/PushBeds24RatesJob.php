<?php

namespace App\Jobs;

use App\Jobs\Concerns\TracksCronRun;
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
    use Queueable, TracksCronRun;

    public string $connection = 'sync';

    public int $tries = 3;

    public function handle(ChannelManager $channels, PricingEngine $pricing): void
    {
        $this->trackCronRun(function () use ($channels, $pricing): void {
            $from = Carbon::today();
            $to = $from->copy()->addDays(90);
            $rowsByAccount = [];

            ChannelMapping::query()
                ->where('status', 'active')
                ->with(['account', 'room'])
                ->each(function (ChannelMapping $mapping) use (&$rowsByAccount, $pricing, $from, $to): void {
                    $account = $mapping->account;
                    $room = $mapping->room;

                    if (! $account || ! $account->isSyncEligible() || ! $room instanceof Room) {
                        return;
                    }

                    try {
                        $quote = $pricing->calculateForRange($room, $from, $to);
                        $longStay = $pricing->lengthOfStayDiscountForRoom($room);
                        $nightlyRate = (float) ($quote['per_night'][$from->toDateString()] ?? $room->base_rate ?? 0);

                        if ($longStay['pct'] > 0) {
                            $nightlyRate = round($nightlyRate * (1 - $longStay['pct'] / 100), 2);
                        }

                        $row = [
                            'roomId' => $mapping->external_room_id,
                            'from' => $from->toDateString(),
                            'to' => $to->toDateString(),
                            'price' => $nightlyRate,
                            'minimumStay' => $quote['minimum_stay'],
                        ];

                        $rowsByAccount[$account->id]['account'] = $account;
                        $rowsByAccount[$account->id]['rows'][] = $row;
                    } catch (\Throwable $e) {
                        Log::warning('Failed to push channel rates', [
                            'mapping_id' => $mapping->id,
                            'message' => $e->getMessage(),
                        ]);
                    }
                });

            foreach ($rowsByAccount as $entry) {
                $account = $entry['account'];

                try {
                    $pushed = $channels->provider($account->provider)->pushRates($account, $entry['rows']);
                    Log::info('Channel rates bulk-pushed to provider', [
                        'account_id' => $account->id,
                        'provider' => $account->provider,
                        'rooms' => count($entry['rows']),
                        'pushed' => $pushed,
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('Failed to bulk-push channel rates', [
                        'account_id' => $account->id,
                        'message' => $e->getMessage(),
                    ]);
                }
            }
        });
    }
}