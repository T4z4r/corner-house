<?php

namespace Tests\Feature;

use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\KnowledgeBaseArticle;
use App\Models\Property;
use App\Models\Room;
use App\Models\Setting;
use App\Models\User;
use App\Services\AI\AiAssistantService;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ChatAssistantTest extends TestCase
{
    use RefreshDatabase;

    public function test_faq_question_uses_knowledge_base(): void
    {
        KnowledgeBaseArticle::factory()->create([
            'category' => 'parking',
            'title' => 'Is parking available?',
            'content' => 'Yes, off-street parking is available for guests.',
            'status' => 'active',
        ]);

        $this->postJson('/api/v1/chat', [
            'message' => 'Do you have parking?',
            'session_id' => 'chat-1',
        ])->assertOk()
            ->assertJsonPath('intent', 'faq')
            ->assertSee('parking', false);

        $this->assertDatabaseHas('ai_conversations', ['session_id' => 'chat-1']);
        $this->assertDatabaseCount('ai_messages', 2);
    }

    public function test_availability_question_uses_availability_service(): void
    {
        $room = Room::factory()->create(['status' => 'active', 'name' => 'Oak Suite']);
        $checkIn = now()->addDays(40)->toDateString();
        $checkOut = now()->addDays(42)->toDateString();

        $this->postJson('/api/v1/chat', [
            'message' => "Is a room available {$checkIn} {$checkOut}?",
            'session_id' => 'chat-avail',
        ])->assertOk()
            ->assertJsonPath('intent', 'availability')
            ->assertSee('Oak Suite', false);
    }

    public function test_property_question_uses_property_details(): void
    {
        $property = Property::factory()->create([
            'name' => 'Harbour View House',
            'city' => 'Brighton',
            'capacity' => 6,
            'bedrooms' => 3,
            'bathrooms' => 2,
            'check_in_from' => '16:00',
            'check_out_until' => '11:00',
        ]);

        $this->postJson('/api/v1/chat', [
            'message' => 'Tell me about the property and its amenities?',
            'session_id' => 'chat-property',
        ])->assertOk()
            ->assertJsonPath('intent', 'property')
            ->assertSee('Harbour View House', false)
            ->assertSee('Brighton', false);
    }

    public function test_rooms_question_uses_room_details(): void
    {
        $property = Property::factory()->create(['name' => 'Harbour View House']);
        Room::factory()->create([
            'property_id' => $property->id,
            'name' => 'The Admiral Suite',
            'type' => 'studio',
            'sleeps' => 2,
            'bedrooms' => 1,
            'bathrooms' => 1,
            'base_rate' => 145,
            'status' => 'active',
        ]);

        $this->postJson('/api/v1/chat', [
            'message' => 'What rooms do you have?',
            'session_id' => 'chat-rooms',
        ])->assertOk()
            ->assertJsonPath('intent', 'rooms')
            ->assertSee('The Admiral Suite', false);
    }

    public function test_rooms_question_is_not_matched_as_property(): void
    {
        Property::factory()->create(['name' => 'Harbour View House']);
        Room::factory()->create(['name' => 'The Admiral Suite', 'status' => 'active']);

        $this->postJson('/api/v1/chat', [
            'message' => 'Tell me about the rooms',
            'session_id' => 'chat-rooms-2',
        ])->assertOk()
            ->assertJsonPath('intent', 'rooms');
    }

    public function test_availability_without_dates_requests_dates_and_booking_page(): void
    {
        Property::factory()->create();

        $this->postJson('/api/v1/chat', [
            'message' => 'Do you have availability?',
            'session_id' => 'chat-avail-nodates',
        ])->assertOk()
            ->assertJsonPath('intent', 'availability')
            ->assertSee('specific dates', false);
    }

    public function test_availability_accepts_relative_tomorrow_dates(): void
    {
        $property = Property::factory()->create();
        Room::factory()->create(['property_id' => $property->id, 'status' => 'active', 'name' => 'Oak Suite']);

        $this->postJson('/api/v1/chat', [
            'message' => 'Are you available tomorrow?',
            'session_id' => 'chat-avail-tomorrow',
        ])->assertOk()
            ->assertJsonPath('intent', 'availability')
            ->assertSee('Oak Suite', false);
    }

    public function test_weather_and_event_question_uses_area_intelligence(): void
    {
        Property::factory()->create(['latitude' => 52.234, 'longitude' => -0.893]);

        KnowledgeBaseArticle::factory()->create([
            'title' => 'Summer art fair',
            'category' => 'area-event',
            'content' => 'A local art fair will run all weekend near the property.',
            'status' => 'active',
            'show_on_website' => true,
        ]);

        Http::fake([
            'api.open-meteo.com/*' => Http::response([
                'daily' => [
                    'time' => [now()->toDateString(), now()->addDay()->toDateString()],
                    'temperature_2m_max' => [24.1, 22.8],
                    'temperature_2m_min' => [15.0, 13.8],
                    'precipitation_probability_max' => [10, 35],
                    'weathercode' => [1, 2],
                ],
            ], 200),
        ]);

        $this->postJson('/api/v1/chat', [
            'message' => 'What is the weather like and are there any events nearby?',
            'session_id' => 'chat-area',
        ])->assertOk()
            ->assertJsonPath('intent', 'area')
            ->assertSee('Summer art fair', false)
            ->assertSee('Mainly clear', false);
    }

    public function test_assistant_does_not_confirm_bookings_in_chat(): void
    {
        $result = app(AiAssistantService::class)->ask('Please book the room for me', 'chat-book');

        $this->assertSame('booking', $result['intent']);
        $this->assertStringContainsString('cannot confirm a reservation in chat', strtolower($result['reply']));
    }

    public function test_guest_manager_can_add_knowledge_article(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole(Role::findByName('Guest Manager'));

        $this->actingAs($user)
            ->post(route('admin.chatbot.articles.store'), [
                'category' => 'amenities',
                'title' => 'Is there Wi-Fi?',
                'content' => 'Yes, complimentary Wi-Fi.',
                'status' => 'active',
                'priority' => 1,
            ])->assertRedirect();

        $this->assertDatabaseHas('knowledge_base_articles', ['title' => 'Is there Wi-Fi?']);
    }

    public function test_guest_manager_can_publish_a_chatbot_question_to_faq_with_visibility_toggle(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole(Role::findByName('Guest Manager'));

        $conversation = AiConversation::query()->create([
            'session_id' => 'faq-publish-thread',
            'source' => 'website',
            'status' => 'open',
        ]);

        $message = AiMessage::query()->create([
            'ai_conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => 'Do you offer early check-in?',
        ]);

        $this->actingAs($user)
            ->post(route('admin.chatbot.articles.store'), [
                'category' => 'faqs',
                'title' => 'Do you offer early check-in?',
                'content' => 'Early check-in is available by request and subject to availability.',
                'priority' => 2,
                'status' => 'active',
                'show_on_website' => '0',
                'source_message_id' => $message->id,
            ])->assertRedirect();

        $this->assertDatabaseHas('knowledge_base_articles', [
            'title' => 'Do you offer early check-in?',
            'source' => 'chatbot',
            'source_message_id' => $message->id,
            'show_on_website' => false,
        ]);
    }

    public function test_openai_provider_is_used_when_configured(): void
    {
        $this->seed(SettingsSeeder::class);
        Setting::query()->where('key', 'ai_provider')->update(['value' => 'openai']);
        Setting::query()->where('key', 'openai_api_key')->update(['value' => Setting::encryptSecret('sk-test-openai')]);
        cache()->forget('settings.all');

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [['message' => ['content' => 'OpenAI parking reply']]],
            ], 200),
        ]);

        $this->postJson('/api/v1/chat', [
            'message' => 'Tell me about the garden',
            'session_id' => 'chat-openai',
            'source' => 'admin',
        ])->assertOk()->assertJsonPath('reply', 'OpenAI parking reply');

        $this->assertDatabaseHas('ai_conversations', [
            'session_id' => 'chat-openai',
            'source' => 'admin',
        ]);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'api.openai.com'));
    }

    public function test_claude_provider_is_used_when_configured(): void
    {
        $this->seed(SettingsSeeder::class);
        Setting::query()->where('key', 'ai_provider')->update(['value' => 'claude']);
        Setting::query()->where('key', 'claude_api_key')->update(['value' => Setting::encryptSecret('sk-test-claude')]);
        cache()->forget('settings.all');

        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [['text' => 'Claude parking reply']],
            ], 200),
        ]);

        $this->postJson('/api/v1/chat', [
            'message' => 'Tell me about the garden',
            'session_id' => 'chat-claude',
        ])->assertOk()->assertJsonPath('reply', 'Claude parking reply');

        Http::assertSent(fn ($request) => str_contains($request->url(), 'api.anthropic.com'));
    }

    public function test_auto_respond_off_does_not_create_assistant_reply(): void
    {
        $this->seed(SettingsSeeder::class);
        Setting::query()->where('key', 'ai_auto_respond')->update(['value' => '0']);
        cache()->forget('settings.all');

        Http::fake();

        $this->postJson('/api/v1/chat', [
            'message' => 'Hello there',
            'session_id' => 'chat-manual',
        ])->assertOk()
            ->assertJsonPath('auto_responded', false);

        $this->assertDatabaseHas('ai_messages', ['role' => 'user', 'content' => 'Hello there']);
        $this->assertSame(0, AiMessage::query()->where('role', 'assistant')->count());
        Http::assertNothingSent();
    }

    public function test_guest_can_leave_a_message_and_receive_auto_reply(): void
    {
        Mail::fake();
        $this->seed(SettingsSeeder::class);
        KnowledgeBaseArticle::factory()->create([
            'category' => 'parking',
            'title' => 'Parking',
            'content' => 'Yes, off-street parking is available for guests.',
            'status' => 'active',
        ]);

        $this->postJson('/api/v1/messages', [
            'name' => 'Sam Guest',
            'email' => 'sam@example.com',
            'message' => 'Do you have parking?',
        ])->assertOk()->assertJsonPath('auto_replied', true);

        $this->assertDatabaseHas('communications', [
            'direction' => 'inbound',
            'recipient' => 'sam@example.com',
        ]);
        $this->assertDatabaseHas('communications', [
            'direction' => 'outbound',
            'recipient' => 'sam@example.com',
        ]);
        $this->assertDatabaseHas('guests', ['email' => 'sam@example.com', 'first_name' => 'Sam']);
    }

    public function test_staff_can_reply_to_a_conversation(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole(Role::findByName('Guest Manager'));
        $conversation = AiConversation::query()->create([
            'session_id' => 'staff-thread',
            'source' => 'website',
            'status' => 'open',
        ]);

        $this->actingAs($user)
            ->post(route('admin.chatbot.conversations.reply', $conversation), [
                'message' => 'Check-in is from 4pm.',
            ])->assertRedirect();

        $this->assertDatabaseHas('ai_messages', [
            'ai_conversation_id' => $conversation->id,
            'intent' => 'staff',
            'content' => 'Check-in is from 4pm.',
        ]);
    }
}
