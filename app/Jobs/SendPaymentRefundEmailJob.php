<?php

namespace App\Jobs;

use App\Models\Payment;
use App\Models\Refund;
use App\Services\Notification\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendPaymentRefundEmailJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $refundId) {}

    public function handle(NotificationService $notifications): void
    {
        $refund = Refund::query()->with('payment.reservation')->find($this->refundId);

        if (! $refund || ! $refund->payment?->reservation) {
            return;
        }

        $reasonLine = trim((string) $refund->reason) !== ''
            ? "\n\nReason: ".$refund->reason
            : '';

        $notifications->sendForEvent('payment_refund', $refund->payment->reservation, [
            '{{refund_amount}}' => number_format((float) $refund->amount, 2),
            '{{reason_line}}' => $reasonLine,
        ]);
    }
}