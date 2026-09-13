<?php

namespace App\Jobs;

use App\Models\Reservation;
use App\Services\Notification\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendCheckoutNotificationJob implements ShouldQueue
{
    use Queueable;

    public function handle(NotificationService $notifications): void
    {
        Reservation::query()
            ->with(['guest', 'room', 'property'])
            ->whereIn('status', ['checked_in', 'checked_out', 'confirmed'])
            ->whereDate('check_out', now()->toDateString())
            ->each(function (Reservation $reservation) use ($notifications): void {
                $communication = $notifications->sendForEvent('check_out', $reservation);

                if (! $communication) {
                    return;
                }

                Log::info('Check-out notification delivered', [
                    'reservation_id' => $reservation->id,
                    'reference' => $reservation->reference,
                    'communication_id' => $communication->id,
                    'recipient' => $communication->recipient,
                    'status' => $communication->status,
                ]);
            });
    }
}
