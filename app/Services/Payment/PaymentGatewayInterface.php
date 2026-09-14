<?php

namespace App\Services\Payment;

interface PaymentGatewayInterface
{
    /**
     * @param  array{
     *     amount: float,
     *     currency: string,
     *     description: string,
     *     success_url: string,
     *     cancel_url: string,
     *     metadata: array<string, string>
     * }  $payload
     * @return array{id: string, url: string}
     */
    public function createCheckoutSession(array $payload): array;

    /**
     * @return array{id: string, payment_status: string, payment_intent: ?string}
     */
    public function retrieveCheckoutSession(string $sessionId): array;

    /**
     * @param  array{
     *     amount: float,
     *     currency: string,
     *     description: string,
     *     customer_email: ?string,
     *     metadata: array<string, string>
     * }  $payload
     * @return array{id: string, client_secret: string, status: string}
     */
    public function createPaymentIntent(array $payload): array;

    /**
     * @return array{id: string, status: string, amount: int}
     */
    public function retrievePaymentIntent(string $paymentIntentId): array;

    /**
     * @return array{id: string, status: string, amount: int}
     */
    public function refund(string $paymentIntentId, int $amountPence): array;

    /**
     * @return array<string, mixed>
     */
    public function parseWebhook(string $payload, string $signature): array;
}
