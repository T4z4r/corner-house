<?php

namespace App\Services\Payment;

use App\Jobs\PushBeds24BookingJob;
use App\Jobs\SendBookingConfirmationJob;
use App\Jobs\SendPaymentRefundEmailJob;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\Reservation;
use App\Services\Audit\AuditLogger;
use App\Services\Booking\BookingService;
use App\Services\Notification\SystemNotificationService;
use App\Services\Notification\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    public function __construct(
        private readonly PaymentGatewayInterface $gateway,
        private readonly BookingService $bookingService,
        private readonly AuditLogger $auditLogger,
        private readonly SystemNotificationService $systemNotifications,
        private readonly NotificationService $notifications,
        private readonly SecurityDepositService $securityDeposits,
    ) {}

    /**
     * @return array{livemode: bool, available: array<array{currency: string, formatted: string}>, pending: array<array{currency: string, formatted: string}>}
     */
    public function balance(): array
    {
        $balance = $this->gateway->retrieveBalance();

        foreach (['available', 'pending'] as $type) {
            $balance[$type] = array_map(function (array $entry): array {
                $currency = strtolower($entry['currency']);
                $decimals = in_array($currency, ['bif', 'clp', 'djf', 'gnf', 'jpy', 'kmf', 'krw', 'mga', 'pyg', 'rwf', 'vnd', 'vuv', 'xaf', 'xof', 'xpf'], true) ? 0 : 2;

                return [
                    'currency' => strtoupper($currency),
                    'formatted' => number_format($entry['amount'] / (10 ** $decimals), $decimals),
                ];
            }, $balance[$type]);
        }

        return $balance;
    }

    public function startCheckout(Reservation $reservation, string $successUrl, string $cancelUrl, ?float $amount = null): Payment
    {
        $chargeAmount = $amount ?? (float) $reservation->total_amount;
        $chargeFullBalance = abs($chargeAmount - (float) $reservation->total_amount) <= 0.01;

        $payment = Payment::create([
            'reservation_id' => $reservation->id,
            'guest_id' => $reservation->guest_id,
            'provider' => 'stripe',
            'amount' => $chargeAmount,
            'currency' => $reservation->property?->currency ?? 'GBP',
            'status' => 'pending',
        ]);

        $lineItems = [];

        if ($chargeFullBalance) {
            $roomName = $reservation->room?->name ?? 'Accommodation';
            $nights = $reservation->nights_count;
            $stayDates = $reservation->check_in?->format('d M Y').' → '.$reservation->check_out?->format('d M Y').' ('.$nights.' night'.($nights > 1 ? 's' : '').')';

            $subtotal = (float) $reservation->subtotal_amount;
            if ($subtotal > 0) {
                $lineItems[] = [
                    'name' => 'Stay at '.$roomName,
                    'description' => $stayDates,
                    'amount' => $subtotal,
                    'quantity' => 1,
                ];
            }

            if ((float) $reservation->damage_deposit > 0) {
                $lineItems[] = [
                    'name' => 'Damage Deposit (refundable)',
                    'description' => 'Refunded after check-out inspection',
                    'amount' => (float) $reservation->damage_deposit,
                    'quantity' => 1,
                ];
            }

            if ($reservation->relationLoaded('addons') || $reservation->addons()->exists()) {
                foreach ($reservation->addons as $addon) {
                    $unitPrice = (float) ($addon->pivot->unit_price ?? $addon->price);
                    $qty = (int) ($addon->pivot->quantity ?? 1);
                    if ($unitPrice > 0) {
                        $lineItems[] = [
                            'name' => $addon->name,
                            'description' => 'Add-on package',
                            'amount' => $unitPrice,
                            'quantity' => $qty,
                        ];
                    }
                }
            }

            // Use itemized line items only if they match the reservation total
            $lineItemsSum = 0;
            foreach ($lineItems as $item) {
                $lineItemsSum += ((float) $item['amount']) * ((int) ($item['quantity'] ?? 1));
            }

            if ($lineItems === [] || abs($lineItemsSum - (float) $reservation->total_amount) > 0.01) {
                $lineItems = [];
            }
        } elseif ((float) $reservation->paid_amount > 0) {
            $lineItems[] = [
                'name' => 'Corner House booking balance',
                'description' => 'Remaining balance for booking '.$reservation->reference,
                'amount' => $chargeAmount,
                'quantity' => 1,
            ];
        } else {
            $balanceDue = max(0.0, round((float) $reservation->total_amount - $chargeAmount, 2));
            $lineItems[] = [
                'name' => 'Corner House deposit (refundable)',
                'description' => $balanceDue > 0
                    ? 'Refundable security deposit - full balance of £'.number_format($balanceDue, 2).' due before arrival'
                    : 'Refundable security deposit',
                'amount' => $chargeAmount,
                'quantity' => 1,
            ];
        }

        $session = $this->gateway->createCheckoutSession([
            'amount' => $chargeAmount,
            'currency' => $payment->currency,
            'description' => 'Corner House booking '.$reservation->reference,
            'customer_email' => $reservation->guest?->email,
            'line_items' => $lineItems,
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'metadata' => [
                'reservation_id' => (string) $reservation->id,
                'payment_id' => (string) $payment->id,
                'reference' => $reservation->reference,
            ],
        ]);

        $payment->update([
            'provider_session_id' => $session['id'],
            'metadata' => ['checkout_url' => $session['url']],
        ]);

        return $payment->fresh();
    }

    public function checkoutUrl(Payment $payment): ?string
    {
        return $payment->metadata['checkout_url'] ?? null;
    }

    public function createIntent(Reservation $reservation, ?float $amount = null): Payment
    {
        $chargeAmount = $amount ?? (float) $reservation->total_amount;

        $payment = $reservation->payments()
            ->where('provider', 'stripe')
            ->where('status', 'pending')
            ->where(fn ($query) => $query->whereNull('metadata->purpose')->orWhere('metadata->purpose', '!=', 'security_deposit'))
            ->where('amount', $chargeAmount)
            ->latest('id')
            ->first()
            ?? Payment::create([
                'reservation_id' => $reservation->id,
                'guest_id' => $reservation->guest_id,
                'provider' => 'stripe',
                'amount' => $chargeAmount,
                'currency' => $reservation->property?->currency ?? 'GBP',
                'status' => 'pending',
            ]);

        if ($payment->provider_payment_id && ! blank($this->clientSecret($payment))) {
            return $payment->fresh();
        }

        $intent = $this->gateway->createPaymentIntent([
            'amount' => $chargeAmount,
            'currency' => $payment->currency,
            'description' => 'Corner House booking '.$reservation->reference,
            'customer_email' => $reservation->guest?->email,
            'metadata' => [
                'reservation_id' => (string) $reservation->id,
                'payment_id' => (string) $payment->id,
                'reference' => $reservation->reference,
            ],
        ]);

        $payment->update([
            'provider_payment_id' => $intent['id'],
            'amount' => $chargeAmount,
            'metadata' => array_merge($payment->metadata ?? [], [
                'intent_status' => $intent['status'],
                'client_secret' => $intent['client_secret'],
            ]),
        ]);

        return $payment->fresh();
    }

    public function clientSecret(Payment $payment): ?string
    {
        $secret = $payment->metadata['client_secret'] ?? null;

        return is_string($secret) ? $secret : null;
    }

    public function confirmFromIntent(string $paymentIntentId): Payment
    {
        $payment = Payment::query()->where('provider_payment_id', $paymentIntentId)->first();

        if (! $payment) {
            throw new \DomainException('Unknown payment intent.');
        }

        if ($payment->isSecurityDeposit()) {
            return $this->securityDeposits->sync($payment);
        }

        if ($payment->isPaid()) {
            return $payment;
        }

        $intent = $this->gateway->retrievePaymentIntent($paymentIntentId);

        if (($intent['status'] ?? '') !== 'succeeded') {
            throw new \DomainException('Payment has not been completed.');
        }

        return $this->markPaid($payment, $paymentIntentId);
    }

    public function confirmFromSession(string $sessionId): Payment
    {
        $payment = Payment::query()->where('provider_session_id', $sessionId)->firstOrFail();

        $session = $this->gateway->retrieveCheckoutSession($sessionId);

        if (($session['payment_status'] ?? '') !== 'paid') {
            throw new \DomainException('Payment has not been completed.');
        }

        return $this->markPaid($payment, $session['payment_intent'] ?? null);
    }

    public function handleWebhook(string $payload, string $signature): void
    {
        $event = $this->gateway->parseWebhook($payload, $signature);
        $type = $event['type'] ?? '';

        if (in_array($type, ['payment_intent.amount_capturable_updated', 'payment_intent.canceled', 'payment_intent.succeeded'], true)) {
            $payment = Payment::query()->where('provider_payment_id', $event['data']['object']['id'] ?? '')->first();
            if ($payment?->isSecurityDeposit()) {
                $this->securityDeposits->sync($payment);

                return;
            }
        }

        if ($type === 'checkout.session.completed') {
            $this->handleCheckoutSessionCompleted($event);

            return;
        }

        if ($type === 'payment_intent.succeeded') {
            $this->handlePaymentIntentSucceeded($event);
        }
    }

    private function handleCheckoutSessionCompleted(array $event): void
    {
        $session = $event['data']['object'] ?? [];
        $sessionId = $session['id'] ?? null;

        if (! $sessionId) {
            return;
        }

        $payment = Payment::query()->where('provider_session_id', $sessionId)->first();

        if (! $payment) {
            Log::warning('Stripe webhook for unknown session', ['session_id' => $sessionId]);

            return;
        }

        if (($session['payment_status'] ?? '') === 'paid' || ($session['status'] ?? '') === 'complete') {
            $this->markPaid($payment, is_string($session['payment_intent'] ?? null) ? $session['payment_intent'] : null);
        }
    }

    private function handlePaymentIntentSucceeded(array $event): void
    {
        $intent = $event['data']['object'] ?? [];
        $intentId = $intent['id'] ?? null;

        if (! $intentId) {
            return;
        }

        $payment = Payment::query()->where('provider_payment_id', $intentId)->first();

        if (! $payment) {
            Log::warning('Stripe webhook for unknown payment intent', ['payment_intent' => $intentId]);

            return;
        }

        if (($intent['status'] ?? '') === 'succeeded') {
            $this->markPaid($payment, $intentId);
        }
    }

    public function markPaid(Payment $payment, ?string $paymentIntentId = null): Payment
    {
        if ($payment->isSecurityDeposit()) {
            return $this->securityDeposits->sync($payment);
        }
        if ($payment->isPaid()) {
            return $payment;
        }

        return DB::transaction(function () use ($payment, $paymentIntentId): Payment {
            $locked = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($locked->isPaid()) {
                return $locked;
            }

            $locked->update([
                'status' => 'paid',
                'provider_payment_id' => $paymentIntentId ?? $locked->provider_payment_id,
                'paid_at' => now(),
            ]);

            $reservation = Reservation::query()->whereKey($locked->reservation_id)->lockForUpdate()->firstOrFail();
            $wasAlreadyConfirmed = $reservation->status === 'confirmed';

            $paidAmount = round((float) $reservation->paid_amount + (float) $locked->amount, 2);
            $fullyPaid = $paidAmount >= (float) $reservation->total_amount - 0.01;
            $reservation->update([
                'paid_amount' => $paidAmount,
                'payment_status' => $fullyPaid ? 'paid' : 'partial',
            ]);

            $this->bookingService->confirm($reservation);

            // confirm() only dispatches the Beds24 push when it transitions the
            // booking to confirmed. A booking confirmed before the payment
            // arrived (e.g. confirmed by an admin first) would otherwise never be
            // posted, so re-dispatch here to guarantee a confirmed payment syncs
            // to Beds24. In the usual hold -> confirmed flow this is skipped to
            // avoid a duplicate push.
            if ($wasAlreadyConfirmed) {
                PushBeds24BookingJob::dispatch($reservation->id);

                // The reservation was already confirmed (e.g. by an admin) before this
                // payment arrived, so confirm() above skipped afterConfirm() and its
                // guest email. Dispatch it here too — sendForEvent() is idempotent per
                // reservation/template, so a normal hold -> paid -> confirmed flow (which
                // already sends this via afterConfirm()) is not double-emailed.
                SendBookingConfirmationJob::dispatch($reservation->id);
            }

            $this->systemNotifications->paymentMarkedPaid($locked->fresh(['reservation']), null);
            $this->auditLogger->log('payments.paid', 'payments', 'payment', (string) $locked->id);

            return $locked->fresh();
        });
    }

    public function deletePending(Payment $payment): void
    {
        DB::transaction(function () use ($payment): void {
            $locked = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== 'pending' || $locked->paid_at !== null || $locked->refunds()->exists()) {
                throw new \DomainException('Only unpaid pending payments without refunds can be deleted.');
            }

            if ($locked->provider === 'stripe') {
                $this->gateway->cancelPendingPayment($locked->provider_session_id, $locked->provider_payment_id);
            }

            $this->auditLogger->log('payments.deleted', 'payments', 'payment', (string) $locked->id, oldValues: [
                'reservation_id' => $locked->reservation_id, 'amount' => $locked->amount, 'currency' => $locked->currency,
                'provider_session_id' => $locked->provider_session_id, 'provider_payment_id' => $locked->provider_payment_id,
            ]);
            $locked->delete();
        });
    }

    public function refund(Payment $payment, ?float $amount = null, ?string $reason = null, ?int $userId = null): Refund
    {
        $communication = null;
        $refund = DB::transaction(function () use ($payment, $amount, $reason, $userId, &$communication): Refund {
            $locked = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isPaid()) {
                throw new \DomainException('Only paid payments can be refunded.');
            }

            $refundAmount = $amount ?? (float) $locked->amount;

            if ($refundAmount <= 0 || $refundAmount > (float) $locked->amount) {
                throw new \DomainException('Invalid refund amount.');
            }

            if (! $locked->provider_payment_id) {
                throw new \DomainException('Payment is missing a provider payment id.');
            }

            $result = $this->gateway->refund($locked->provider_payment_id, (int) round($refundAmount * 100));

            $refund = Refund::create([
                'payment_id' => $locked->id,
                'reservation_id' => $locked->reservation_id,
                'amount' => $refundAmount,
                'status' => $result['status'] === 'succeeded' ? 'succeeded' : 'pending',
                'provider_refund_id' => $result['id'],
                'reason' => $reason,
                'created_by' => $userId,
            ]);

            $locked->update(['status' => 'refunded']);
            if (! $locked->isSecurityDeposit()) {
                $locked->reservation?->update(['payment_status' => 'refunded']);
            }

            $this->systemNotifications->paymentRefunded($locked->fresh(['reservation']), $refund, $userId);
            $this->auditLogger->log('payments.refunded', 'payments', 'payment', (string) $locked->id);

            $communication = $this->notifications->prepareRefund($refund);


            return $refund;
        });

        if ($communication) {
            SendPaymentRefundEmailJob::dispatch($refund->id, $communication->id)->afterCommit();
        }

        return $refund;
    }
}
