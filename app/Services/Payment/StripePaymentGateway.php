<?php

namespace App\Services\Payment;

use App\Models\Setting;
use Stripe\StripeClient;
use Stripe\Webhook;

class StripePaymentGateway implements PaymentGatewayInterface
{
    public function __construct(private readonly StripeClient $client) {}

    public function createCheckoutSession(array $payload): array
    {
        $session = $this->client->checkout->sessions->create([
            'mode' => 'payment',
            'success_url' => $payload['success_url'],
            'cancel_url' => $payload['cancel_url'],
            'metadata' => $payload['metadata'] ?? [],
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => strtolower($payload['currency']),
                    'unit_amount' => (int) round($payload['amount'] * 100),
                    'product_data' => [
                        'name' => $payload['description'],
                    ],
                ],
            ]],
        ]);

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
        $secret = (string) Setting::getValue('stripe_webhook_secret', config('services.stripe.webhook_secret', ''));

        $event = Webhook::constructEvent($payload, $signature, $secret);

        return $event->toArray();
    }
}
