<?php

namespace App\Services\Beds24;

use App\Models\ChannelAccount;
use App\Models\Communication;
use App\Models\Reservation;
use App\Services\Channel\ChannelManager;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class Beds24MessageService
{
    public function __construct(private readonly ChannelManager $channels) {}

    /**
     * Fetch latest OTA messages from Beds24 for all active accounts and store
     * any unseen messages as Communication records, linked to the local
     * reservation (and guest) where one can be resolved.
     *
     * @return array{total: int, created: int, updated: int, failed: int}
     */
    public function syncAll(): array
    {
        $summary = ['total' => 0, 'created' => 0, 'updated' => 0, 'failed' => 0];

        ChannelAccount::query()
            ->where('provider', 'beds24')
            ->where('status', 'active')
            ->each(function (ChannelAccount $account) use (&$summary): void {
                $accountSummary = $this->syncAccount($account);
                foreach ($accountSummary as $key => $value) {
                    $summary[$key] += $value;
                }
            });

        return $summary;
    }

    /**
     * @return array{total: int, created: int, updated: int, failed: int}
     */
    public function syncAccount(ChannelAccount $account): array
    {
        $params = $account->last_message_synced_at
            ? ['maxAge' => $account->last_message_synced_at->toIso8601String()]
            : [];

        $messages = $this->fetch($account, $params);
        $summary = ['total' => count($messages), 'created' => 0, 'updated' => 0, 'failed' => 0];

        foreach ($messages as $message) {
            try {
                $result = $this->ingest($account, $message);

                if ($result === 'created') {
                    $summary['created']++;
                } elseif ($result === 'updated') {
                    $summary['updated']++;
                }
            } catch (\Throwable $e) {
                $summary['failed']++;
                Log::warning('Failed to ingest Beds24 message', [
                    'account_id' => $account->id,
                    'message_id' => $message['id'] ?? null,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $account->update([
            'last_message_synced_at' => now(),
            'last_message_sync_status' => $summary['failed'] > 0 ? 'failed' : 'success',
        ]);

        return $summary;
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<int, array<string, mixed>>
     */
    public function fetch(ChannelAccount $account, array $params = []): array
    {
        return $this->channels->provider('beds24')->fetchMessages($account, $params);
    }

    /**
     * Store or update a single Beds24 message as a Communication record.
     *
     * @param  array<string, mixed>  $message
     */
    public function ingest(ChannelAccount $account, array $message): string
    {
        $messageId = (string) ($message['id'] ?? '');
        if ($messageId === '') {
            return 'skipped';
        }

        $bookingId = (string) ($message['bookingId'] ?? '');
        $reservation = $this->resolveReservation($account, $bookingId);
        $direction = $this->direction($message);

        $payload = [
            'channel' => 'beds24',
            'direction' => $direction,
            'recipient' => $this->recipient($message, $reservation),
            'sender_name' => $direction === 'inbound' ? ($message['source'] ?? 'Guest') : null,
            'subject' => $reservation
                ? 'Re: '.$reservation->reference.' (Beds24 booking '.$bookingId.')'
                : 'Beds24 booking '.$bookingId,
            'body' => (string) ($message['message'] ?? ''),
            'status' => ($message['read'] ?? false) ? 'sent' : 'pending',
            'provider_message_id' => $bookingId !== '' ? $messageId.'-'.$bookingId : $messageId,
            'sent_at' => isset($message['time']) ? Carbon::parse($message['time']) : now(),
            'metadata' => [
                'beds24_message_id' => $messageId,
                'beds24_booking_id' => $bookingId,
                'source' => $message['source'] ?? null,
                'read' => (bool) ($message['read'] ?? false),
            ],
        ];

        if ($reservation) {
            $payload['reservation_id'] = $reservation->id;
            $payload['guest_id'] = $reservation->guest_id;
        }

        $existing = Communication::query()
            ->where('provider_message_id', $payload['provider_message_id'])
            ->first();

        if ($existing) {
            $existing->update([
                'body' => $payload['body'],
                'status' => $payload['status'],
                'sent_at' => $payload['sent_at'],
                'metadata' => $payload['metadata'],
            ]);

            return 'updated';
        }

        Communication::create($payload);

        return 'created';
    }

    /**
     * Persist an outbound reply and send it back to Beds24 for the given
     * booking and channel (Airbnb / Booking.com etc).
     */
    public function reply(Communication $communication, string $body): Communication
    {
        $bookingId = (string) ($communication->metadata['beds24_booking_id'] ?? '');
        $account = $this->activeAccount();

        if ($bookingId === '' || ! $account) {
            throw new \DomainException('Cannot reply: no linked Beds24 booking or account.');
        }

        $this->channels->provider('beds24')->sendMessage($account, [
            'bookingId' => (int) $bookingId,
            'message' => $body,
        ]);

        $communication->update([
            'body' => $body,
            'status' => 'sent',
            'sent_at' => now(),
            'metadata' => array_merge($communication->metadata ?? [], ['reply_sent' => true]),
        ]);

        return $communication->fresh();
    }

    private function resolveReservation(ChannelAccount $account, string $bookingId): ?Reservation
    {
        if ($bookingId === '') {
            return null;
        }

        return Reservation::query()
            ->where('external_channel', $account->provider)
            ->where('external_booking_id', $bookingId)
            ->with('guest')
            ->first();
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function direction(array $message): string
    {
        $source = strtolower((string) ($message['source'] ?? ''));
        $authorOwnerId = $message['authorOwnerId'] ?? null;

        if ($source === 'guest' || $source === 'channel' || $source === 'ota' || $authorOwnerId === null) {
            return 'inbound';
        }

        return 'outbound';
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function recipient(array $message, ?Reservation $reservation): string
    {
        if ($reservation?->guest?->email) {
            return $reservation->guest->email;
        }

        if (! empty($message['source'])) {
            return 'via '.$message['source'];
        }

        return 'unknown';
    }

    private function activeAccount(): ?ChannelAccount
    {
        return ChannelAccount::query()
            ->where('provider', 'beds24')
            ->where('status', 'active')
            ->first();
    }
}
