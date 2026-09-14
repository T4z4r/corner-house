<?php

namespace App\Jobs;

use App\Mail\NewBookingNotificationMail;
use App\Models\Reservation;
use App\Models\Setting;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendNewDirectBookingNotificationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $reservationId) {}

    public function handle(): void
    {
        $reservation = Reservation::query()->with(['guest', 'room', 'property'])->find($this->reservationId);

        if (! $reservation || $reservation->source !== 'direct') {
            return;
        }

        $recipient = trim((string) Setting::getValue('booking_notify_email', ''));

        if ($recipient === '') {
            return;
        }

        try {
            Mail::to($recipient)->send(new NewBookingNotificationMail($reservation));
        } catch (\Throwable $e) {
            Log::warning('Failed to send new direct booking notification', [
                'reservation_id' => $reservation->id,
                'recipient' => $recipient,
                'message' => $e->getMessage(),
            ]);
        }
    }
}