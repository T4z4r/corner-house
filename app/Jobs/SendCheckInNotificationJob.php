<?php

namespace App\Jobs;

use App\Models\Reservation;
use App\Services\Notification\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendCheckInNotificationJob implements ShouldQueue
{
    use Queueable;

    public function handle(NotificationService $notifications): void
    {
        Reservation::query()
            ->with(['guest', 'room', 'property'])
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->whereDate('check_in', now()->toDateString())
            ->each(fn (Reservation $reservation) => $notifications->sendForEvent('check_in', $reservation));
    }
}
