<?php

namespace App\Jobs;

use App\Models\Reservation;
use App\Services\Notification\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendCheckoutNotificationJob implements ShouldQueue
{
    use Queueable;

    public function handle(NotificationService $notifications): void
    {
        Reservation::query()
            ->with(['guest', 'room', 'property'])
            ->whereIn('status', ['checked_in', 'checked_out', 'confirmed'])
            ->whereDate('check_out', now()->toDateString())
            ->each(fn (Reservation $reservation) => $notifications->sendForEvent('check_out', $reservation));
    }
}
