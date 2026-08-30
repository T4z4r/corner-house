<?php

namespace Tests\Feature;

use App\Models\ChannelAccount;
use App\Models\ChannelMapping;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ChannelWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_beds24_webhook_creates_reservation_idempotently(): void
    {
        $room = Room::factory()->create(['status' => 'active', 'base_rate' => 90]);
        $account = ChannelAccount::factory()->create(['provider' => 'beds24', 'status' => 'active']);
        ChannelMapping::factory()->create([
            'channel_account_id' => $account->id,
            'property_id' => $room->property_id,
            'room_id' => $room->id,
            'provider' => 'beds24',
            'external_room_id' => '555',
        ]);

        $payload = [
            'id' => 'B24-100',
            'roomId' => '555',
            'checkIn' => now()->addDays(30)->toDateString(),
            'checkOut' => now()->addDays(33)->toDateString(),
            'firstName' => 'Jamie',
            'lastName' => 'Channel',
            'email' => 'jamie@example.com',
            'status' => 'confirmed',
            'channel' => 'airbnb',
        ];

        $this->postJson('/webhooks/beds24', $payload)->assertOk();
        $this->postJson('/webhooks/beds24', $payload)->assertOk();

        $this->assertSame(1, Reservation::query()->count());
        $this->assertDatabaseHas('reservations', [
            'external_channel' => 'beds24',
            'external_booking_id' => 'B24-100',
            'room_id' => $room->id,
            'status' => 'confirmed',
        ]);
        $this->assertDatabaseCount('channel_webhooks', 2);
    }

    public function test_beds24_webhook_handles_wrapped_body_payloads(): void
    {
        $room = Room::factory()->create(['status' => 'active', 'base_rate' => 90]);
        $account = ChannelAccount::factory()->create(['provider' => 'beds24', 'status' => 'active']);
        ChannelMapping::factory()->create([
            'channel_account_id' => $account->id,
            'property_id' => $room->property_id,
            'room_id' => $room->id,
            'provider' => 'beds24',
            'external_room_id' => '723357',
        ]);

        $payload = [
            'body' => [
                'data' => [[
                    'id' => 'B24-200',
                    'roomId' => '723357',
                    'unitId' => '1',
                    'arrival' => now()->addDays(30)->toDateString(),
                    'departure' => now()->addDays(32)->toDateString(),
                    'firstName' => '',
                    'lastName' => '',
                    'status' => 'confirmed',
                    'channel' => 'direct',
                    'numAdult' => 2,
                ]],
            ],
        ];

        $this->postJson('/webhooks/beds24', $payload)->assertOk();

        $this->assertDatabaseHas('reservations', [
            'external_channel' => 'beds24',
            'external_booking_id' => 'B24-200',
            'room_id' => $room->id,
            'status' => 'confirmed',
        ]);
    }

    public function test_unmapped_webhook_does_not_create_reservation(): void
    {
        ChannelAccount::factory()->create(['provider' => 'beds24', 'status' => 'active']);

        $this->postJson('/webhooks/beds24', [
            'id' => 'B24-404',
            'roomId' => 'missing',
            'checkIn' => now()->addDays(5)->toDateString(),
            'checkOut' => now()->addDays(7)->toDateString(),
        ])->assertOk();

        $this->assertDatabaseCount('reservations', 0);
    }

    public function test_super_admin_can_view_channels_page(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole(Role::findByName('Super Admin'));

        $this->actingAs($user)
            ->get(route('admin.channels.integrations'))
            ->assertOk()
            ->assertSee('Beds24 integrations')
            ->assertSee('API test window')
            ->assertSee('Swagger');
    }
}
