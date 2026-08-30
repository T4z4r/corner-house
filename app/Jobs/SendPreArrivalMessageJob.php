<?php

namespace App\Jobs;

use App\Models\Reservation;
use App\Services\Notification\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendPreArrivalMessageJob implements ShouldQueue
{
    use Queueable;

    public function handle(NotificationService $notifications): void
    {
        Reservation::query()
            ->with(['guest', 'room', 'property'])
            ->where('status', 'confirmed')
            ->whereDate('check_in', now()->addDay()->toDateString())
            ->each(fn (Reservation $reservation) => $notifications->sendForEvent('pre_arrival', $reservation));
    }
}
