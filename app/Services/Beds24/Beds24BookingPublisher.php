<?php

namespace App\Services\Beds24;

use App\Models\ChannelAccount;
use App\Models\ChannelMapping;
use App\Models\Reservation;
use Illuminate\Support\Facades\Log;

class Beds24BookingPublisher
{
    public function __construct(private readonly Beds24Client $client) {}

    public function postBooking(Reservation $reservation): bool
    {
        $account = $this->activeAccount();
        if (! $account instanceof ChannelAccount) {
            return false;
        }

        if ($reservation->status === 'cancelled' && empty($reservation->external_booking_id)) {
            return false;
        }

        $reservation->loadMissing(['guest', 'guests']);
        $payload = $this->bookingPayload($account, $reservation);
        if ($payload === []) {
            return false;
        }

        try {
            $response = $this->client->post($account, 'bookings', [$payload]);
            $this->syncReservation($reservation, $response);

            return true;
        } catch (\Throwable $e) {
            Log::warning('Failed to post booking to Beds24', [
                'reservation_id' => $reservation->id,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function activeAccount(): ?ChannelAccount
    {
        return ChannelAccount::query()
            ->where('provider', 'beds24')
            ->where('status', 'active')
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function bookingPayload(ChannelAccount $account, Reservation $reservation): array
    {
        $roomMapping = ChannelMapping::query()
            ->where('channel_account_id', $account->id)
            ->where('provider', 'beds24')
            ->where('room_id', $reservation->room_id)
            ->whereNotNull('external_room_id')
            ->first();

        if (! $roomMapping) {
            return [];
        }

        $guest = $reservation->guest;
        $additionalGuests = $reservation->guests
            ->map(static function ($guest): array {
                return array_filter([
                    'firstName' => $guest->first_name,
                    'lastName' => $guest->last_name,
                    'email' => $guest->email,
                    'phone' => $guest->phone,
                    'type' => $guest->type,
                ], static fn ($value) => $value !== null && $value !== '');
            })
            ->values()
            ->all();

        $guests = array_filter(array_merge([
            array_filter([
                'firstName' => $guest?->first_name,
                'lastName' => $guest?->last_name,
                'email' => $guest?->email,
                'phone' => $guest?->phone,
                'country' => $guest?->country,
            ], static fn ($value) => $value !== null && $value !== ''),
        ], $additionalGuests), static fn ($value) => is_array($value) && $value !== []);

        return array_filter([
            'id' => $reservation->external_booking_id ? (int) $reservation->external_booking_id : null,
            'roomId' => (int) $roomMapping->external_room_id,
            'arrival' => $reservation->check_in?->toDateString(),
            'departure' => $reservation->check_out?->toDateString(),
            'numAdult' => $reservation->guests_count,
            'firstName' => $guest?->first_name,
            'lastName' => $guest?->last_name,
            'email' => $guest?->email,
            'phone' => $guest?->phone,
            'guests' => $guests !== [] ? $guests : null,
            'status' => $reservation->status,
            'reference' => $reservation->reference,
            'notes' => $reservation->notes,
            'source' => $reservation->source,
            'channel' => $reservation->channel,
        ], static fn ($value) => $value !== null && $value !== '');
    }

    /**
     * @param  array<string, mixed>  $response
     */
    private function syncReservation(Reservation $reservation, array $response): void
    {
        $externalBookingId = (string) data_get($response, 'data.0.id', data_get($response, 'id', ''));
        if ($externalBookingId === '') {
            return;
        }

        $reservation->update([
            'external_channel' => 'beds24',
            'external_booking_id' => $externalBookingId,
            'sync_status' => 'synced',
        ]);
    }
}
