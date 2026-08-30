<?php

namespace App\Services\Channel;

use App\Models\ChannelAccount;
use App\Models\ChannelMapping;
use App\Models\ChannelSyncLog;
use App\Models\ChannelWebhook;
use App\Models\Guest;
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
        $syncLog = ChannelSyncLog::create([
            'channel_account_id' => $account->id,
            'channel' => $account->provider,
            'operation' => 'sync_bookings',
            'request' => ['full' => $full, 'params' => $params ?? []],
            'response' => ['raw_count' => count($bookings)],
            'status' => 'pending',
            'started_at' => now(),
        ]);

        $count = 0;
        $failed = [];
        $skipped = [];
        $successes = [];

        foreach ($bookings as $booking) {
            $externalId = (string) ($booking['id'] ?? $booking['bookId'] ?? $booking['bookingId'] ?? 'unknown');

            try {
                $result = $this->ingestExternalBooking($account, $booking);

                if ($result['status'] === 'created' || $result['status'] === 'updated') {
                    $count++;
                    $successes[] = ['id' => $externalId, 'status' => $result['status']];
                } else {
                    $skipped[] = ['id' => $externalId, 'reason' => $result['reason']];
                }
            } catch (\Throwable $e) {
                $failed[] = ['id' => $externalId, 'error' => $e->getMessage()];
                Log::warning('Failed to ingest channel booking', [
                    'account_id' => $account->id,
                    'external_id' => $externalId,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        $syncLog->update([
            'status' => $failed !== [] ? 'failed' : 'success',
            'response' => [
                'total' => count($bookings),
                'created_or_updated' => $count,
                'skipped' => count($skipped),
                'failed' => count($failed),
                'successes' => $successes,
                'skipped_details' => $skipped,
                'failed_details' => $failed,
            ],
            'error_message' => $failed !== [] ? count($failed).' booking(s) failed to import' : null,
            'completed_at' => now(),
        ]);

        $account->update([
            'last_synced_at' => now(),
            'last_error' => $failed !== [] ? count($failed).' booking(s) failed to import' : null,
            'status' => 'active',
        ]);

        if ($skipped !== [] || $failed !== []) {
            Log::info('Channel booking sync summary', [
                'account_id' => $account->id,
                'total' => count($bookings),
                'imported' => $count,
                'skipped' => count($skipped),
                'failed' => count($failed),
                'skip_reasons' => array_column($skipped, 'reason'),
            ]);
        }

        return $count;
    }

    /**
     * Ingest a single external booking and return the result status.
     *
     * @param  array<string, mixed>  $booking
     * @return array{status: string, reason?: string}
     */
    public function ingestExternalBooking(ChannelAccount $account, array $booking): array
    {
        $externalId = (string) ($booking['id'] ?? $booking['bookId'] ?? $booking['bookingId'] ?? '');

        if ($externalId === '') {
            return ['status' => 'skipped', 'reason' => 'missing_external_id'];
        }

        // Try multiple field names for room/property identification
        $roomId = $booking['roomId'] ?? $booking['unitId'] ?? $booking['roomTypeId'] ?? null;
        $propertyId = $booking['propertyId'] ?? $booking['propId'] ?? null;

        $mapping = ChannelMapping::query()
            ->where('channel_account_id', $account->id)
            ->where(function ($query) use ($roomId, $propertyId): void {
                if ($roomId) {
                    $query->where('external_room_id', (string) $roomId);
                } elseif ($propertyId) {
                    $query->where('external_property_id', (string) $propertyId);
                }
            })
            ->first();

        if (! $mapping) {
            return ['status' => 'skipped', 'reason' => 'no_mapping_found (roomId='.$roomId.', propertyId='.$propertyId.')'];
        }

        if (! $mapping->room_id) {
            return ['status' => 'skipped', 'reason' => 'mapping_exists_but_room_id_null (mapping_id='.$mapping->id.', external_room_id='.$mapping->external_room_id.')'];
        }

        $checkIn = $booking['arrival'] ?? $booking['checkIn'] ?? $booking['firstNight'] ?? $booking['from'] ?? null;
        $departure = $booking['departure'] ?? $booking['checkOut'] ?? $booking['lastNight'] ?? $booking['to'] ?? null;
        $lastNight = $booking['lastNight'] ?? null;

        if (! $checkIn || (! $departure && ! $lastNight)) {
            return ['status' => 'skipped', 'reason' => 'missing_dates (checkIn='.$checkIn.', departure='.$departure.', lastNight='.$lastNight.')'];
        }

        $checkOut = $departure
            ? Carbon::parse($departure)
            : Carbon::parse($lastNight)->addDay();

        $status = strtolower((string) ($booking['status'] ?? 'confirmed'));
        $cancelled = in_array($status, ['cancelled', 'canceled', 'cancel', '0', 'rejected'], true);
        $checkInDate = Carbon::parse($checkIn)->toDateString();
        $checkOutDate = $checkOut->toDateString();
        $source = (string) ($booking['channel'] ?? $account->provider);

        // Try multiple price field names
        $total = $booking['price']
            ?? $booking['priceTotal']
            ?? $booking['total']
            ?? $booking['amount']
            ?? $booking['totalPrice']
            ?? $booking['netPrice']
            ?? null;

        $existing = Reservation::query()
            ->where('external_channel', $account->provider)
            ->where('external_booking_id', $externalId)
            ->first();

        if ($existing) {
            if ($cancelled) {
                $this->bookingService->cancel($existing, 'Cancelled by channel');

                return ['status' => 'updated', 'reason' => 'cancelled'];
            }

            $overlap = Reservation::query()
                ->active()
                ->overlapsDates($mapping->room_id, $checkInDate, $checkOutDate)
                ->where('id', '!=', $existing->id)
                ->exists();

            if ($overlap) {
                Log::warning('Channel booking overlaps a local reservation', [
                    'external_id' => $externalId,
                    'room_id' => $mapping->room_id,
                    'check_in' => $checkInDate,
                    'check_out' => $checkOutDate,
                ]);

                return ['status' => 'skipped', 'reason' => 'overlaps_existing_reservation'];
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
                'total_amount' => $total !== null ? (float) $total : $existing->total_amount,
                'base_amount' => $total !== null ? (float) $total : $existing->base_amount,
                'cancelled_at' => null,
                'notes' => $booking['notes'] ?? $existing->notes,
            ]);

            $this->syncReservationGuest($existing->fresh(['guest']), $booking);

            return ['status' => 'updated'];
        }

        $result = $this->bookingService->create([
            'room_id' => $mapping->room_id,
            'check_in' => $checkInDate,
            'check_out' => $checkOutDate,
            'guests_count' => (int) ($booking['numAdult'] ?? $booking['numAdults'] ?? $booking['guests'] ?? 1),
            'guest_email' => $booking['email'] ?? null,
            'guest_first_name' => $booking['firstName'] ?? $booking['guestFirstName'] ?? 'Guest',
            'guest_last_name' => $booking['lastName'] ?? $booking['guestLastName'] ?? '',
            'guest_phone' => $booking['phone'] ?? $booking['guestPhone'] ?? null,
            'guest_country' => $booking['country'] ?? $booking['guestCountry'] ?? null,
            'status' => $cancelled ? 'cancelled' : 'confirmed',
            'source' => $source,
            'channel' => $account->provider,
            'external_channel' => $account->provider,
            'external_booking_id' => $externalId,
            'skip_sync' => true,
            'notes' => $booking['notes'] ?? null,
        ]);

        if ($cancelled) {
            $this->bookingService->cancel($result['reservation'], 'Cancelled by channel');
        }

        if ($total !== null && ! $cancelled) {
            $result['reservation']->update([
                'total_amount' => (float) $total,
                'base_amount' => (float) $total,
                'sync_status' => 'synced',
            ]);
        }

        return ['status' => 'created'];
    }

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
        if (isset($payload['body']) && is_array($payload['body'])) {
            $payload = $payload['body'];
        }

        $bookings = $payload['data'] ?? $payload['bookings'] ?? $payload['booking'] ?? $payload;

        if (isset($bookings['id']) || isset($bookings['bookId'])) {
            return [$bookings];
        }

        if (! is_array($bookings)) {
            return [];
        }

        return array_values(array_filter($bookings, 'is_array'));
    }

    /**
     * @param  array<string, mixed>  $booking
     */
    private function syncReservationGuest(Reservation $reservation, array $booking): void
    {
        $guestData = $this->guestDataFromBooking($booking);
        if ($guestData === []) {
            return;
        }

        $guest = $reservation->guest;

        if ($guest instanceof Guest) {
            $guest->update($guestData);

            return;
        }

        if (! empty($guestData['email'])) {
            $guest = Guest::query()->firstOrNew(['email' => $guestData['email']]);
            $guest->fill($guestData);
            $guest->save();

            $reservation->update(['guest_id' => $guest->id]);

            return;
        }

        $guest = Guest::query()->create($guestData);
        $reservation->update(['guest_id' => $guest->id]);
    }

    /**
     * @param  array<string, mixed>  $booking
     * @return array<string, mixed>
     */
    private function guestDataFromBooking(array $booking): array
    {
        return array_filter([
            'first_name' => $booking['firstName'] ?? $booking['guestFirstName'] ?? null,
            'last_name' => $booking['lastName'] ?? $booking['guestLastName'] ?? null,
            'email' => $booking['email'] ?? $booking['guestEmail'] ?? null,
            'phone' => $booking['phone'] ?? $booking['guestPhone'] ?? null,
            'country' => $booking['country'] ?? $booking['guestCountry'] ?? null,
            'source' => $booking['channel'] ?? null,
        ], static fn ($value) => $value !== null && $value !== '');
    }
}
