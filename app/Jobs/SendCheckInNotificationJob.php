<?php

namespace App\Jobs;

use App\Models\Reservation;
use App\Services\Notification\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendCheckInNotificationJob implements ShouldQueue
{
    use Queueable;

    public function handle(NotificationService $notifications): void
    {
        Reservation::query()
            ->with(['guest', 'room', 'property'])
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->whereDate('check_in', now()->toDateString())
            ->each(function (Reservation $reservation) use ($notifications): void {
                $communication = $notifications->sendForEvent('check_in', $reservation);

                if (! $communication) {
                    return;
                }

                Log::info('Check-in notification delivered', [
                    'reservation_id' => $reservation->id,
                    'reference' => $reservation->reference,
                    'communication_id' => $communication->id,
                    'recipient' => $communication->recipient,
                    'status' => $communication->status,
                ]);
            });
    }
}
