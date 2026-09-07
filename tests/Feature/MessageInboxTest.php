<?php

namespace Tests\Feature;

use App\Models\ChannelAccount;
use App\Models\Communication;
use App\Models\Reservation;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SettingsSeeder;
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

    private function inboundMessage(array $attributes = []): Communication
    {
        return Communication::factory()->create(array_merge([
            'channel' => 'beds24',
            'direction' => 'inbound',
            'body' => 'Is late checkout possible?',
            'sender_name' => 'Jane Doe',
            'status' => 'pending',
            'metadata' => ['beds24_booking_id' => 1001, 'beds24_message_id' => 550],
        ], $attributes));
    }

    private function configureOpenAi(array $reply): void
    {
        $this->seed(SettingsSeeder::class);
        Setting::query()->where('key', 'ai_provider')->update(['value' => 'openai']);
        Setting::query()->where('key', 'openai_api_key')->update(['value' => Setting::encryptSecret('sk-test-openai')]);
        cache()->forget('settings.all');

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [['message' => ['content' => $reply['content'] ?? 'Thanks for asking!']]],
            ], 200),
        ]);
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

    public function test_super_admin_can_ai_draft_a_reply_for_an_inbound_message(): void
    {
        $user = $this->adminUser();
        $reservation = Reservation::factory()->create([
            'reference' => 'CH-ABC123',
            'check_in' => now()->addDays(10)->toDateString(),
            'check_out' => now()->addDays(12)->toDateString(),
        ]);
        $message = $this->inboundMessage(['reservation_id' => $reservation->id]);

        $this->configureOpenAi(['content' => 'Of course, late checkout is usually available.']);

        $this->actingAs($user)
            ->getJson(route('admin.messages.draft', $message))
            ->assertOk()
            ->assertJsonPath('draft', 'Of course, late checkout is usually available.');

        Http::assertSent(function ($request) use ($reservation, $message): bool {
            $payload = $request->data();
            $userPrompt = $payload['messages'][1]['content'] ?? '';

            return str_contains($userPrompt, $message->body)
                && str_contains($userPrompt, 'Jane Doe')
                && str_contains($userPrompt, $reservation->reference);
        });

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'messages.drafted',
            'record_type' => 'communication',
            'record_id' => (string) $message->id,
        ]);
    }

    public function test_ai_draft_is_denied_without_communications_send_permission(): void
    {
        $user = User::factory()->create();
        $message = $this->inboundMessage();

        $this->actingAs($user)
            ->getJson(route('admin.messages.draft', $message))
            ->assertForbidden();
    }

    public function test_ai_draft_returns_error_when_no_provider_configured(): void
    {
        $user = $this->adminUser();
        $message = $this->inboundMessage();

        $response = $this->actingAs($user)
            ->getJson(route('admin.messages.draft', $message));

        $response->assertStatus(422)
            ->assertJsonStructure(['error']);
    }

    public function test_ai_draft_is_not_available_for_outbound_messages(): void
    {
        $user = $this->adminUser();
        $message = $this->inboundMessage(['direction' => 'outbound']);

        $this->actingAs($user)
            ->getJson(route('admin.messages.draft', $message))
            ->assertNotFound();
    }

    public function test_show_page_includes_ai_draft_button(): void
    {
        $user = $this->adminUser();
        $message = $this->inboundMessage();

        $this->actingAs($user)
            ->get(route('admin.messages.show', $message))
            ->assertOk()
            ->assertSee('Draft with AI');
    }
}
