<?php

namespace App\Jobs;

use App\Mail\NewEnquiryNotificationMail;
use App\Models\Enquiry;
use App\Models\Setting;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendNewEnquiryNotificationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $enquiryId) {}

    public function handle(): void
    {
        $enquiry = Enquiry::query()->find($this->enquiryId);

        if (! $enquiry) {
            return;
        }

        $recipient = trim((string) (Setting::getValue('admin_notification_email') ?: Setting::getValue('booking_notify_email', '')));

        if ($recipient === '') {
            return;
        }

        try {
            Mail::to($recipient)->send(new NewEnquiryNotificationMail($enquiry));
        } catch (\Throwable $e) {
            Log::warning('Failed to send new enquiry notification', [
                'enquiry_id' => $enquiry->id,
                'recipient' => $recipient,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
