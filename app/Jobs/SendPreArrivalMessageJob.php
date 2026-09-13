<?php

namespace App\Jobs;

use App\Jobs\Concerns\TracksCronRun;
use App\Models\Reservation;
use App\Services\Notification\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendPreArrivalMessageJob implements ShouldQueue
{
    use Queueable, TracksCronRun;

    public function handle(NotificationService $notifications): void
    {
        $this->trackCronRun(function () use ($notifications): void {
            Reservation::query()
                ->with(['guest', 'room', 'property'])
                ->where('status', 'confirmed')
                ->whereDate('check_in', now()->addDay()->toDateString())
                ->each(function (Reservation $reservation) use ($notifications): void {
                    $communication = $notifications->sendForEvent('pre_arrival', $reservation);

                    if (! $communication) {
                        return;
                    }

                    Log::info('Pre-arrival guest message delivered', [
                        'reservation_id' => $reservation->id,
                        'reference' => $reservation->reference,
                        'communication_id' => $communication->id,
                        'recipient' => $communication->recipient,
                        'status' => $communication->status,
                    ]);
                });
        });
    }
}