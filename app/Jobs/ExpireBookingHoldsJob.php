<?php

namespace App\Jobs;

use App\Jobs\Concerns\TracksCronRun;
use App\Services\Booking\BookingHoldService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ExpireBookingHoldsJob implements ShouldQueue
{
    use Queueable, TracksCronRun;

    public function handle(BookingHoldService $holds): void
    {
        $this->trackCronRun(function () use ($holds): void {
            $released = $holds->expireExpiredHolds();

            Log::info('Booking holds expired', ['released' => $released]);
        });
    }
}