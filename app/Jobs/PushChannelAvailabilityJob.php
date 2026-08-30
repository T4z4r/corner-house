<?php

namespace App\Jobs;

use App\Models\ChannelMapping;
use App\Models\Reservation;
use App\Services\Channel\ChannelManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class PushChannelAvailabilityJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $reservationId) {}

    public function handle(ChannelManager $channels): void
    {
        $reservation = Reservation::query()->find($this->reservationId);

        if (! $reservation) {
            return;
        }

        $mappings = ChannelMapping::query()
            ->where('room_id', $reservation->room_id)
            ->where('status', 'active')
            ->with('account')
            ->get();

        foreach ($mappings as $mapping) {
            $account = $mapping->account;

            if (! $account || $account->status !== 'active') {
                continue;
            }

            try {
                $lastNight = $reservation->check_out->copy()->subDay();
                if ($lastNight->lt($reservation->check_in)) {
                    $lastNight = $reservation->check_in->copy();
                }

                $channels->provider($account->provider)->pushAvailability($account, [[
                    'roomId' => $mapping->external_room_id,
                    'from' => $reservation->check_in->toDateString(),
                    'to' => $lastNight->toDateString(),
                    'numAvail' => $reservation->status === 'cancelled' ? 1 : 0,
                ]]);
            } catch (\Throwable $e) {
                Log::warning('Failed to push channel availability', [
                    'reservation_id' => $reservation->id,
                    'message' => $e->getMessage(),
                ]);
            }
        }
    }
}
