<?php

namespace App\Jobs;

use App\Services\Booking\BookingHoldService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ExpireBookingHoldsJob implements ShouldQueue
{
    use Queueable;

    public function handle(BookingHoldService $holds): void
    {
        $holds->expireExpiredHolds();
    }
}
