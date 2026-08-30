<?php

namespace App\Jobs;

use App\Models\Reservation;
use App\Services\Beds24\Beds24BookingPublisher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class PushBeds24BookingJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $reservationId) {}

    public function handle(Beds24BookingPublisher $publisher): void
    {
        $reservation = Reservation::query()->find($this->reservationId);

        if (! $reservation) {
            return;
        }

        $publisher->postBooking($reservation);
    }
}
