<?php

namespace App\Services\Payment;

use DomainException;
use Stripe\StripeClient;

class InstantPayoutService
{
    public function __construct(private readonly StripeClient $client) {}

    /** @return array{account: string, livemode: bool, destinations: array<int, array{id: string, label: string, currency: string, available: int}>} */
    public function options(): array
    {
        $account = $this->client->accounts->retrieve();
        $balance = $this->client->balance->retrieve();
        $available = [];
        foreach ($balance->instant_available ?? [] as $entry) {
            $available[$entry->currency] = max(0, min((int) $entry->amount, (int) ($entry->source_types->card ?? 0)));
        }

        $destinations = [];
        if ($account->payouts_enabled) {
            $accounts = $this->client->accounts->allExternalAccounts($account->id, ['limit' => 100]);
            foreach ($accounts->autoPagingIterator() as $destination) {
                if (! in_array('instant', $destination->available_payout_methods ?? [], true)) {
                    continue;
                }
                $currency = strtolower((string) $destination->currency);
                if (($available[$currency] ?? 0) <= 0) {
                    continue;
                }
                $destinations[] = [
                    'id' => (string) $destination->id,
                    'label' => ($destination->bank_name ?? $destination->brand ?? 'Payout account').' ending '.$destination->last4,
                    'currency' => $currency,
                    'available' => $available[$currency],
                ];
            }
        }

        return ['account' => $account->id, 'livemode' => (bool) $balance->livemode, 'destinations' => $destinations];
    }

    /** @return array{account: string, livemode: bool, destination: string, label: string, amount: int, currency: string} */
    public function review(string $destinationId, string $amount): array
    {
        $options = $this->options();
        $destination = collect($options['destinations'])->firstWhere('id', $destinationId);
        if (! $destination) {
            throw new DomainException('This destination is not currently eligible for an Instant Payout.');
        }

        $parts = explode('.', $amount, 2);
        $minorAmount = ((int) $parts[0] * 100) + (int) str_pad($parts[1] ?? '', 2, '0');
        if ($minorAmount <= 0 || $minorAmount > $destination['available']) {
            throw new DomainException('Enter an amount within the available instant payout balance.');
        }

        return [
            'account' => $options['account'], 'livemode' => $options['livemode'],
            'destination' => $destination['id'], 'label' => $destination['label'],
            'amount' => $minorAmount, 'currency' => $destination['currency'],
        ];
    }

    /**
     * @param  array{account: string, livemode: bool, destination: string, amount: int, currency: string, user_id: int, key: string}  $review
     * @return array{id: string, status: string}
     */
    public function send(array $review): array
    {
        $account = $this->client->accounts->retrieve();
        $balance = $this->client->balance->retrieve();
        if ($account->id !== $review['account'] || (bool) $balance->livemode !== $review['livemode']) {
            throw new DomainException('The Stripe account or mode has changed. Review the payout again.');
        }

        $payout = $this->client->payouts->create([
            'amount' => $review['amount'], 'currency' => $review['currency'],
            'destination' => $review['destination'], 'method' => 'instant', 'source_type' => 'card',
            'metadata' => ['requested_by' => (string) $review['user_id']],
        ], ['idempotency_key' => $review['key']]);

        return ['id' => (string) $payout->id, 'status' => (string) $payout->status];
    }
}
