<?php

namespace App\Jobs;

use App\Models\Communication;
use App\Models\Refund;
use App\Services\Mail\MailDispatchService;
use App\Services\Notification\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendPaymentRefundEmailJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $refundId, public ?int $communicationId = null) {}

    public function handle(NotificationService $notifications, MailDispatchService $mailer): void
    {
        if ($this->communicationId !== null) {
            $communication = Communication::find($this->communicationId);
        } else {
        $refund = Refund::query()->with('payment.reservation')->find($this->refundId);

        if (! $refund || ! $refund->payment?->reservation) {
            return;
        }

            $communication = $notifications->prepareRefund($refund);
        }

        if ($communication && $communication->status !== 'sent') {
            $mailer->send($communication);
        }
    }
}
