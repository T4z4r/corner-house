<?php

namespace App\Services\Channel;

use App\Models\ChannelAccount;
use App\Models\ChannelMapping;
use App\Models\ChannelWebhook;
use App\Models\Reservation;
use App\Services\Audit\AuditLogger;
use App\Services\Beds24\Beds24ChannelProvider;
use App\Services\Booking\BookingService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class ChannelManager
{
    /** @var array<string, ChannelProviderInterface> */
    private array $providers = [];

    public function __construct(
        private readonly BookingService $bookingService,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function register(ChannelProviderInterface $provider): void
    {
        $this->providers[$provider->provider()] = $provider;
    }

    public function provider(string $name): ChannelProviderInterface
    {
        if (! isset($this->providers[$name])) {
            throw new \InvalidArgumentException("Unknown channel provider [{$name}].");
        }

        return $this->providers[$name];
    }

    public function syncBookings(ChannelAccount $account, bool $full = false): int
    {
        $provider = $this->provider($account->provider);
        if ($provider instanceof Beds24ChannelProvider) {
            $params = (! $full && $account->last_synced_at)
                ? ['modifiedFrom' => $account->last_synced_at->toIso8601String()]
                : [];
            $payload = $provider->fetchBookings($account, $params);
        } else {
            $payload = $provider->syncBookings($account);
        }

        $bookings = $this->bookingList($payload);
        $count = 0;

        foreach ($bookings as $booking) {
            try {
                $this->ingestExternalBooking($account, $booking);
                $count++;
            } catch (\Throwable $e) {
                Log::warning('Failed to ingest channel booking', [
                    'account_id' => $account->id,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        $account->update([
            'last_synced_at' => now(),
            'last_error' => null,
            'status' => 'active',
        ]);

        return $count;
    }

    /**
     * @param  array<string, mixed>  $booking
     */
    public function ingestExternalBooking(ChannelAccount $account, array $booking): void
    {
        $externalId = (string) ($booking['id'] ?? $booking['bookId'] ?? $booking['bookingId'] ?? '');

        if ($externalId === '') {
            return;
        }

        $mapping = ChannelMapping::query()
            ->where('channel_account_id', $account->id)
            ->where(function ($query) use ($booking): void {
                $roomId = $booking['roomId'] ?? $booking['unitId'] ?? null;
                $propertyId = $booking['propertyId'] ?? null;
                if ($roomId) {
                    $query->where('external_room_id', (string) $roomId);
                } elseif ($propertyId) {
                    $query->where('external_property_id', (string) $propertyId);
                }
            })
            ->first();

        if (! $mapping?->room_id) {
            Log::info('Skipping unmapped channel booking', ['external_id' => $externalId]);

            return;
        }

        $checkIn = $booking['arrival'] ?? $booking['checkIn'] ?? $booking['firstNight'] ?? null;
        $departure = $booking['departure'] ?? $booking['checkOut'] ?? null;
        $lastNight = $booking['lastNight'] ?? null;

        if (! $checkIn || (! $departure && ! $lastNight)) {
            return;
        }

        $checkOut = $departure
            ? Carbon::parse($departure)
            : Carbon::parse($lastNight)->addDay();

        $status = strtolower((string) ($booking['status'] ?? 'confirmed'));
        $cancelled = in_array($status, ['cancelled', 'canceled', 'cancel', '0'], true);
        $checkInDate = Carbon::parse($checkIn)->toDateString();
        $checkOutDate = $checkOut->toDateString();
        $source = (string) ($booking['channel'] ?? $account->provider);
        $total = $booking['price'] ?? $booking['priceTotal'] ?? $booking['total'] ?? null;

        $existing = Reservation::query()
            ->where('external_channel', $account->provider)
            ->where('external_booking_id', $externalId)
            ->first();

        if ($existing) {
            if ($cancelled) {
                $this->bookingService->cancel($existing, 'Cancelled by channel');

                return;
            }

            $overlap = Reservation::query()
                ->active()
                ->overlapsDates($mapping->room_id, $checkInDate, $checkOutDate)
                ->where('id', '!=', $existing->id)
                ->exists();

            if ($overlap) {
                Log::warning('Beds24 booking overlaps a local reservation', ['external_id' => $externalId]);

                return;
            }

            $existing->update([
                'room_id' => $mapping->room_id,
                'property_id' => $mapping->property_id,
                'check_in' => $checkInDate,
                'check_out' => $checkOutDate,
                'guests_count' => (int) ($booking['numAdult'] ?? $booking['numAdults'] ?? $booking['guests'] ?? $existing->guests_count),
                'status' => 'confirmed',
                'source' => $source,
                'channel' => $account->provider,
                'sync_status' => 'synced',
                'total_amount' => $total ?? $existing->total_amount,
                'base_amount' => $total ?? $existing->base_amount,
                'cancelled_at' => null,
            ]);

            return;
        }

        $result = $this->bookingService->create([
            'room_id' => $mapping->room_id,
            'check_in' => $checkInDate,
            'check_out' => $checkOutDate,
            'guests_count' => (int) ($booking['numAdult'] ?? $booking['numAdults'] ?? $booking['guests'] ?? 1),
            'guest_email' => $booking['email'] ?? null,
            'guest_first_name' => $booking['firstName'] ?? $booking['guestFirstName'] ?? 'Guest',
            'guest_last_name' => $booking['lastName'] ?? $booking['guestLastName'] ?? '',
            'status' => $cancelled ? 'cancelled' : 'confirmed',
            'source' => $source,
            'channel' => $account->provider,
            'external_channel' => $account->provider,
            'external_booking_id' => $externalId,
            'skip_sync' => true,
        ]);

        if ($cancelled) {
            $this->bookingService->cancel($result['reservation'], 'Cancelled by channel');
        }

        if ($total !== null && ! $cancelled) {
            $result['reservation']->update([
                'total_amount' => $total,
                'base_amount' => $total,
                'sync_status' => 'synced',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function storeWebhook(string $provider, array $payload, ?string $eventType = null, ?string $externalId = null): ChannelWebhook
    {
        return ChannelWebhook::create([
            'provider' => $provider,
            'event_type' => $eventType,
            'external_id' => $externalId,
            'payload' => $payload,
            'status' => 'received',
        ]);
    }

    public function processWebhook(ChannelWebhook $webhook): void
    {
        $account = ChannelAccount::query()
            ->where('provider', $webhook->provider)
            ->where('status', 'active')
            ->first();

        if (! $account) {
            $webhook->update(['status' => 'ignored', 'processed_at' => now()]);

            return;
        }

        try {
            foreach ($this->bookingList($webhook->payload) as $booking) {
                $this->ingestExternalBooking($account, $booking);
            }
            $webhook->update(['status' => 'processed', 'processed_at' => now()]);
            $this->auditLogger->log('channels.webhook', 'channels', 'channel_webhook', (string) $webhook->id);
        } catch (\Throwable $e) {
            $webhook->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'processed_at' => now(),
            ]);

            throw $e;
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, array<string, mixed>>
     */
    private function bookingList(array $payload): array
    {
        $bookings = $payload['data'] ?? $payload['bookings'] ?? $payload['booking'] ?? $payload;

        if (isset($bookings['id']) || isset($bookings['bookId'])) {
            return [$bookings];
        }

        if (! is_array($bookings)) {
            return [];
        }

        return array_values(array_filter($bookings, 'is_array'));
    }
}
