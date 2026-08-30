<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\Refund;
use App\Models\Reservation;
use App\Services\Audit\AuditLogger;
use App\Services\Booking\BookingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    public function __construct(
        private readonly PaymentGatewayInterface $gateway,
        private readonly BookingService $bookingService,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function startCheckout(Reservation $reservation, string $successUrl, string $cancelUrl): Payment
    {
        $payment = Payment::create([
            'reservation_id' => $reservation->id,
            'guest_id' => $reservation->guest_id,
            'provider' => 'stripe',
            'amount' => $reservation->total_amount,
            'currency' => $reservation->property?->currency ?? 'GBP',
            'status' => 'pending',
        ]);

        $session = $this->gateway->createCheckoutSession([
            'amount' => (float) $reservation->total_amount,
            'currency' => $payment->currency,
            'description' => 'Corner House booking '.$reservation->reference,
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

        if ($type !== 'checkout.session.completed') {
            return;
        }

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

    public function markPaid(Payment $payment, ?string $paymentIntentId = null): Payment
    {
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
            $reservation->update([
                'paid_amount' => $locked->amount,
                'payment_status' => 'paid',
            ]);

            $this->bookingService->confirm($reservation);
            $this->auditLogger->log('payments.paid', 'payments', 'payment', (string) $locked->id);

            return $locked->fresh();
        });
    }

    public function refund(Payment $payment, ?float $amount = null, ?string $reason = null, ?int $userId = null): Refund
    {
        if (! $payment->isPaid()) {
            throw new \DomainException('Only paid payments can be refunded.');
        }

        $refundAmount = $amount ?? (float) $payment->amount;

        if ($refundAmount <= 0 || $refundAmount > (float) $payment->amount) {
            throw new \DomainException('Invalid refund amount.');
        }

        if (! $payment->provider_payment_id) {
            throw new \DomainException('Payment is missing a provider payment id.');
        }

        $result = $this->gateway->refund($payment->provider_payment_id, (int) round($refundAmount * 100));

        $refund = Refund::create([
            'payment_id' => $payment->id,
            'reservation_id' => $payment->reservation_id,
            'amount' => $refundAmount,
            'status' => $result['status'] === 'succeeded' ? 'succeeded' : 'pending',
            'provider_refund_id' => $result['id'],
            'reason' => $reason,
            'created_by' => $userId,
        ]);

        $payment->update(['status' => 'refunded']);
        $payment->reservation?->update(['payment_status' => 'refunded']);

        $this->auditLogger->log('payments.refunded', 'payments', 'payment', (string) $payment->id);

        return $refund;
    }
}
