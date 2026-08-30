<?php

namespace App\Jobs;

use App\Models\Reservation;
use App\Services\Notification\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendBookingConfirmationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $reservationId) {}

    public function handle(NotificationService $notifications): void
    {
        $reservation = Reservation::query()->find($this->reservationId);

        if (! $reservation) {
            return;
        }

        $notifications->sendForEvent('booking_confirmation', $reservation);
        $notifications->sendForEvent('payment_confirmation', $reservation);
    }
}
