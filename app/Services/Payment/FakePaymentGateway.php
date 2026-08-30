<?php

namespace App\Services\Payment;

class FakePaymentGateway implements PaymentGatewayInterface
{
    /** @var array<string, array<string, mixed>> */
    public array $sessions = [];

    public bool $paid = true;

    public function createCheckoutSession(array $payload): array
    {
        $id = 'cs_test_'.uniqid();

        $this->sessions[$id] = $payload;
        $url = str_replace('{CHECKOUT_SESSION_ID}', $id, $payload['success_url']);

        if (! str_contains($url, $id)) {
            $url .= (str_contains($url, '?') ? '&' : '?').'session_id='.$id;
        }

        return [
            'id' => $id,
            'url' => $url,
        ];
    }

    public function retrieveCheckoutSession(string $sessionId): array
    {
        return [
            'id' => $sessionId,
            'payment_status' => $this->paid ? 'paid' : 'unpaid',
            'payment_intent' => 'pi_test_'.$sessionId,
        ];
    }

    public function refund(string $paymentIntentId, int $amountPence): array
    {
        return [
            'id' => 're_test_'.uniqid(),
            'status' => 'succeeded',
            'amount' => $amountPence,
        ];
    }

    public function parseWebhook(string $payload, string $signature): array
    {
        $decoded = json_decode($payload, true);

        if (! is_array($decoded)) {
            throw new \InvalidArgumentException('Invalid webhook payload.');
        }

        return $decoded;
    }
}
