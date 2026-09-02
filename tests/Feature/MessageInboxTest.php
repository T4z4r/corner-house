<?php

namespace Tests\Feature;

use App\Models\ChannelAccount;
use App\Models\Communication;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MessageInboxTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function adminUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findByName('Super Admin'));

        return $user;
    }

    public function test_super_admin_can_view_inbox_with_only_beds24_messages(): void
    {
        $user = $this->adminUser();

        $beds24 = Communication::factory()->create([
            'channel' => 'beds24',
            'direction' => 'inbound',
            'body' => 'Hello from the channel',
            'status' => 'pending',
        ]);
        Communication::factory()->create([
            'channel' => 'email',
            'body' => 'A regular email',
        ]);

        $this->actingAs($user)
            ->get(route('admin.messages.index'))
            ->assertOk()
            ->assertSee('Hello from the channel')
            ->assertDontSee('A regular email');

        $this->assertTrue($beds24->exists);
    }

    public function test_user_without_communications_view_permission_is_denied(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.messages.index'))
            ->assertForbidden();
    }

    public function test_show_page_renders_message_detail_and_reply_form(): void
    {
        $user = $this->adminUser();

        $message = Communication::factory()->create([
            'channel' => 'beds24',
            'direction' => 'inbound',
            'body' => 'Early check-in possible?',
            'status' => 'pending',
            'metadata' => ['beds24_booking_id' => 1001, 'beds24_message_id' => 550],
        ]);

        $this->actingAs($user)
            ->get(route('admin.messages.show', $message))
            ->assertOk()
            ->assertSee('Early check-in possible?')
            ->assertSee('Reply via Beds24');
    }

    public function test_non_beds24_message_cannot_be_viewed_in_inbox(): void
    {
        $user = $this->adminUser();

        $message = Communication::factory()->create(['channel' => 'email']);

        $this->actingAs($user)
            ->get(route('admin.messages.show', $message))
            ->assertNotFound();
    }

    public function test_fetch_endpoint_requires_active_account(): void
    {
        $user = $this->adminUser();

        $this->actingAs($user)
            ->post(route('admin.messages.fetch'))
            ->assertSessionHasErrors();
    }

    public function test_fetch_with_active_account_syncs_messages(): void
    {
        $user = $this->adminUser();

        ChannelAccount::factory()->create([
            'provider' => 'beds24',
            'status' => 'active',
            'credentials' => [
                'access_token' => 'test-token',
                'access_token_expires_at' => now()->addDay()->toIso8601String(),
            ],
        ]);

        Http::fake([
            'https://beds24.com/api/v2/bookings/messages*' => Http::response(['data' => []], 200),
        ]);

        $this->actingAs($user)
            ->post(route('admin.messages.fetch'))
            ->assertRedirect(route('admin.messages.index'))
            ->assertSessionHas('status');
    }
}
