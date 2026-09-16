<?php

namespace App\Services\Payment;

use App\Models\Setting;
use Stripe\StripeClient;
use Stripe\Webhook;

class StripePaymentGateway implements PaymentGatewayInterface
{
    public function __construct(private readonly StripeClient $client) {}

    public function cancelPendingPayment(?string $sessionId, ?string $intentId): void
    {
        $session = $sessionId ? $this->client->checkout->sessions->retrieve($sessionId) : null;
        if ($session && ($session->status === 'complete' || $session->payment_status === 'paid')) {
            throw new \DomainException('Stripe has already completed this checkout. It cannot be deleted.');
        }

        $intent = $intentId ? $this->client->paymentIntents->retrieve($intentId) : null;
        if ($intent && ! in_array($intent->status, ['requires_payment_method', 'requires_confirmation', 'requires_action', 'canceled'], true)) {
            throw new \DomainException('This payment is paid, authorised, or processing at Stripe. It cannot be deleted.');
        }

        if ($session && $session->status === 'open') {
            $this->client->checkout->sessions->expire($sessionId);
        }
        if ($intent && $intent->status !== 'canceled' && $intentId !== ($session?->payment_intent ?? null)) {
            $this->client->paymentIntents->cancel($intentId);
        }
    }

    public function retrieveBalance(): array
    {
        $balance = $this->client->balance->retrieve();

        return [
            'livemode' => (bool) $balance->livemode,
            'available' => array_map(fn ($entry): array => ['amount' => (int) $entry->amount, 'currency' => (string) $entry->currency], $balance->available),
            'pending' => array_map(fn ($entry): array => ['amount' => (int) $entry->amount, 'currency' => (string) $entry->currency], $balance->pending),
        ];
    }

    public function createCheckoutSession(array $payload): array
    {
        $sessionParams = [
            'mode' => 'payment',
            'success_url' => $payload['success_url'],
            'cancel_url' => $payload['cancel_url'],
            'metadata' => $payload['metadata'] ?? [],
        ];

        if (! empty($payload['customer_email'])) {
            $sessionParams['customer_email'] = $payload['customer_email'];
        }

        if (! empty($payload['line_items']) && is_array($payload['line_items'])) {
            $sessionParams['line_items'] = array_map(function (array $item) use ($payload): array {
                $productData = [
                    'name' => (string) ($item['name'] ?? $payload['description']),
                ];
                if (! empty($item['description'])) {
                    $productData['description'] = (string) $item['description'];
                }

                return [
                    'quantity' => (int) ($item['quantity'] ?? 1),
                    'price_data' => [
                        'currency' => strtolower((string) ($item['currency'] ?? $payload['currency'])),
                        'unit_amount' => (int) round(((float) ($item['amount'] ?? 0)) * 100),
                        'product_data' => $productData,
                    ],
                ];
            }, $payload['line_items']);
        } else {
            $sessionParams['line_items'] = [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => strtolower((string) $payload['currency']),
                    'unit_amount' => (int) round(((float) $payload['amount']) * 100),
                    'product_data' => [
                        'name' => (string) $payload['description'],
                    ],
                ],
            ]];
        }

        $session = $this->client->checkout->sessions->create($sessionParams);

        return [
            'id' => $session->id,
            'url' => (string) $session->url,
        ];
    }

    public function retrieveCheckoutSession(string $sessionId): array
    {
        $session = $this->client->checkout->sessions->retrieve($sessionId);

        return [
            'id' => $session->id,
            'payment_status' => (string) $session->payment_status,
            'payment_intent' => is_string($session->payment_intent) ? $session->payment_intent : $session->payment_intent?->id,
        ];
    }

    public function createPaymentIntent(array $payload): array
    {
        $params = [
            'amount' => (int) round(((float) $payload['amount']) * 100),
            'currency' => strtolower((string) $payload['currency']),
            'description' => (string) ($payload['description'] ?? ''),
            'automatic_payment_methods' => ['enabled' => true],
            'metadata' => $payload['metadata'] ?? [],
        ];

        if (($payload['capture_method'] ?? null) === 'manual') {
            unset($params['automatic_payment_methods']);
            $params['payment_method_types'] = ['card'];
            $params['capture_method'] = 'manual';
        }

        if (! empty($payload['customer_email'])) {
            $params['receipt_email'] = (string) $payload['customer_email'];
        }

        $intent = $this->client->paymentIntents->create($params, isset($payload['idempotency_key']) ? ['idempotency_key' => $payload['idempotency_key']] : []);

        return [
            'id' => $intent->id,
            'client_secret' => (string) $intent->client_secret,
            'status' => (string) $intent->status,
        ];
    }

    public function retrievePaymentIntent(string $paymentIntentId): array
    {
        $intent = $this->client->paymentIntents->retrieve($paymentIntentId, ['expand' => ['latest_charge']]);

        return [
            'id' => $intent->id,
            'status' => (string) $intent->status,
            'amount' => (int) $intent->amount,
            'currency' => (string) $intent->currency,
            'capture_before' => $intent->latest_charge?->payment_method_details?->card?->capture_before,
        ];
    }

    public function releaseHold(string $paymentIntentId): void
    {
        $intent = $this->client->paymentIntents->retrieve($paymentIntentId);
        if ($intent->status === 'canceled') {
            return;
        }
        if ($intent->status !== 'requires_capture') {
            throw new \DomainException('This payment is not an active hold. Captured payments must be refunded instead.');
        }
        $this->client->paymentIntents->cancel($paymentIntentId, [], ['idempotency_key' => 'release-'.$paymentIntentId]);
    }

    public function refund(string $paymentIntentId, int $amountPence): array
    {
        $refund = $this->client->refunds->create([
            'payment_intent' => $paymentIntentId,
            'amount' => $amountPence,
        ]);

        return [
            'id' => $refund->id,
            'status' => (string) $refund->status,
            'amount' => (int) $refund->amount,
        ];
    }

    public function parseWebhook(string $payload, string $signature): array
    {
        $secret = Setting::getValue('stripe_webhook_secret');
        if (blank($secret)) {
            $secret = config('services.stripe.webhook_secret', '');
        }

        $event = Webhook::constructEvent($payload, $signature, (string) $secret);

        return $event->toArray();
    }
}
