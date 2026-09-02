<?php

namespace Tests\Feature;

use App\Models\ChannelAccount;
use App\Models\Communication;
use App\Models\Guest;
use App\Models\Reservation;
use App\Models\Room;
use App\Services\Beds24\Beds24MessageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class Beds24MessageSyncTest extends TestCase
{
    use RefreshDatabase;

    private function activeAccount(): ChannelAccount
    {
        return ChannelAccount::factory()->create([
            'provider' => 'beds24',
            'status' => 'active',
            'credentials' => [
                'access_token' => 'test-token',
                'access_token_expires_at' => now()->addDay()->toIso8601String(),
            ],
        ]);
    }

    private function fakeMessagesEndpoint(array $response): void
    {
        Http::fake([
            'https://beds24.com/api/v2/bookings/messages*' => Http::response($response, 200),
        ]);
    }

    public function test_sync_account_stores_new_inbound_message_linked_to_reservation(): void
    {
        $room = Room::factory()->create(['status' => 'active']);
        $guest = Guest::factory()->create(['email' => 'guest@example.com']);
        $reservation = Reservation::factory()->create([
            'room_id' => $room->id,
            'guest_id' => $guest->id,
            'external_channel' => 'beds24',
            'external_booking_id' => '1001',
        ]);
        $account = $this->activeAccount();

        $this->fakeMessagesEndpoint([
            'data' => [[
                'id' => 550,
                'bookingId' => 1001,
                'time' => '2026-09-01T10:00:00Z',
                'read' => false,
                'message' => 'Can we check in early?',
                'source' => 'guest',
            ]],
        ]);

        app(Beds24MessageService::class)->syncAccount($account);

        $this->assertDatabaseHas('communications', [
            'channel' => 'beds24',
            'direction' => 'inbound',
            'reservation_id' => $reservation->id,
            'guest_id' => $guest->id,
            'provider_message_id' => '550-1001',
            'status' => 'pending',
        ]);

        $this->assertSame('success', $account->fresh()->last_message_sync_status);
    }

    public function test_sync_account_is_idempotent(): void
    {
        $room = Room::factory()->create(['status' => 'active']);
        $guest = Guest::factory()->create();
        Reservation::factory()->create([
            'room_id' => $room->id,
            'guest_id' => $guest->id,
            'external_channel' => 'beds24',
            'external_booking_id' => '1001',
        ]);
        $account = $this->activeAccount();

        $this->fakeMessagesEndpoint([
            'data' => [[
                'id' => 550,
                'bookingId' => 1001,
                'time' => '2026-09-01T10:00:00Z',
                'read' => false,
                'message' => 'Hi',
                'source' => 'guest',
            ]],
        ]);

        $service = app(Beds24MessageService::class);
        $service->syncAccount($account);
        $service->syncAccount($account);

        $this->assertSame(1, Communication::query()->count());
    }

    public function test_outbound_owner_message_is_stored_with_outbound_direction(): void
    {
        $account = $this->activeAccount();

        $this->fakeMessagesEndpoint([
            'data' => [[
                'id' => 551,
                'authorOwnerId' => 22,
                'bookingId' => 2002,
                'time' => '2026-09-01T11:00:00Z',
                'read' => true,
                'message' => 'See you soon',
                'source' => 'owner',
            ]],
        ]);

        app(Beds24MessageService::class)->syncAccount($account);

        $this->assertDatabaseHas('communications', [
            'channel' => 'beds24',
            'direction' => 'outbound',
            'provider_message_id' => '551-2002',
            'status' => 'sent',
        ]);
    }

    public function test_message_without_linked_reservation_is_stored_unlinked(): void
    {
        $account = $this->activeAccount();

        $this->fakeMessagesEndpoint([
            'data' => [[
                'id' => 552,
                'bookingId' => 9999,
                'time' => '2026-09-01T12:00:00Z',
                'read' => false,
                'message' => 'A message for an unknown booking',
                'source' => 'guest',
            ]],
        ]);

        app(Beds24MessageService::class)->syncAccount($account);

        $this->assertDatabaseHas('communications', [
            'channel' => 'beds24',
            'direction' => 'inbound',
            'reservation_id' => null,
            'guest_id' => null,
        ]);
    }
}
