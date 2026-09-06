<?php

namespace Tests\Feature;

use App\Models\KnowledgeBaseArticle;
use App\Models\Property;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminEventsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_events_index_lists_events_for_super_admin(): void
    {
        $event = KnowledgeBaseArticle::factory()->create([
            'category' => 'event',
            'title' => 'Braunston Canal Festival',
            'starts_at' => now()->addDays(20)->toDateString(),
            'ends_at' => now()->addDays(22)->toDateString(),
        ]);

        $response = $this->actingAs($this->adminUser())
            ->get(route('admin.events.index'));

        $response->assertOk()
            ->assertSeeText('Events & Holidays')
            ->assertSee('Braunston Canal Festival');
    }

    public function test_events_index_is_forbidden_without_chatbot_permission(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findByName('Finance Manager'));

        $this->actingAs($user)
            ->get(route('admin.events.index'))
            ->assertForbidden();
    }

    public function test_super_admin_can_create_an_event(): void
    {
        $response = $this->actingAs($this->adminUser())
            ->post(route('admin.events.store'), [
                'title' => 'Village Summer Fete',
                'content' => 'A day of stalls, games and live music on the village green.',
                'starts_at' => now()->addMonths(2)->toDateString(),
                'ends_at' => now()->addMonths(2)->addDay()->toDateString(),
                'priority' => 3,
                'status' => 'active',
                'show_on_website' => '1',
            ]);

        $response->assertRedirect(route('admin.events.index'))
            ->assertSessionHas('status', 'Event created.');

        $this->assertDatabaseHas('knowledge_base_articles', [
            'title' => 'Village Summer Fete',
            'category' => 'event',
            'source' => 'manual',
            'status' => 'active',
            'priority' => 3,
            'show_on_website' => true,
        ]);
    }

    public function test_super_admin_can_update_an_event(): void
    {
        $event = KnowledgeBaseArticle::factory()->create([
            'category' => 'event',
            'title' => 'Old Name',
            'starts_at' => now()->addDays(10)->toDateString(),
            'ends_at' => now()->addDays(12)->toDateString(),
        ]);

        $this->actingAs($this->adminUser())
            ->put(route('admin.events.update', $event), [
                'title' => 'New Name',
                'content' => 'Updated description.',
                'starts_at' => now()->addDays(20)->toDateString(),
                'ends_at' => now()->addDays(21)->toDateString(),
                'priority' => 2,
                'status' => 'disabled',
                'show_on_website' => '0',
            ])
            ->assertRedirect(route('admin.events.index'))
            ->assertSessionHas('status', 'Event updated.');

        $this->assertDatabaseHas('knowledge_base_articles', [
            'id' => $event->id,
            'title' => 'New Name',
            'status' => 'disabled',
            'show_on_website' => false,
        ]);
    }

    public function test_super_admin_can_toggle_an_event(): void
    {
        $event = KnowledgeBaseArticle::factory()->create([
            'category' => 'event',
            'status' => 'disabled',
        ]);

        $this->actingAs($this->adminUser())
            ->post(route('admin.events.toggle', $event))
            ->assertRedirect()
            ->assertSessionHas('status', 'Status updated.');

        $this->assertDatabaseHas('knowledge_base_articles', [
            'id' => $event->id,
            'status' => 'active',
        ]);
    }

    public function test_super_admin_can_delete_an_event(): void
    {
        $event = KnowledgeBaseArticle::factory()->create(['category' => 'event']);

        $this->actingAs($this->adminUser())
            ->delete(route('admin.events.destroy', $event))
            ->assertRedirect(route('admin.events.index'))
            ->assertSessionHas('status', 'Event deleted.');

        $this->assertDatabaseMissing('knowledge_base_articles', ['id' => $event->id]);
    }

    public function test_super_admin_can_generate_ai_events(): void
    {
        $this->seed(SettingsSeeder::class);
        Setting::query()->where('key', 'openai_api_key')->update(['value' => Setting::encryptSecret('sk-test-openai')]);
        cache()->forget('settings.all');

        Property::factory()->create(['name' => 'Corner House']);

        Http::fake([
            'api.openai.com/*' => Http::response([
                'output_text' => json_encode([
                    'summary' => 'A lively village calendar for the coming months.',
                    'events' => [
                        [
                            'generation_key' => 'corner-house-fete-2027',
                            'title' => 'Corner House Fete',
                            'category' => 'festival',
                            'location' => 'Village green',
                            'description' => 'An evening festival on the village green.',
                            'start_date' => now()->addMonth()->toDateString(),
                            'end_date' => now()->addMonth()->addDay()->toDateString(),
                            'priority' => 5,
                            'show_on_website' => true,
                        ],
                        [
                            'generation_key' => 'autumn-fair-2027',
                            'title' => 'Autumn Fair',
                            'category' => 'market',
                            'location' => 'Village hall',
                            'description' => 'A seasonal fair with local produce.',
                            'start_date' => now()->addMonths(3)->toDateString(),
                            'end_date' => now()->addMonths(3)->addDays(2)->toDateString(),
                            'priority' => 3,
                            'show_on_website' => false,
                        ],
                    ],
                ], JSON_THROW_ON_ERROR),
            ], 200),
        ]);

        $this->actingAs($this->adminUser())
            ->post(route('admin.events.ai.generate'))
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('knowledge_base_articles', [
            'title' => 'Corner House Fete',
            'category' => 'event',
            'source' => 'ai',
            'status' => 'active',
            'ai_generation_key' => 'corner-house-fete-2027',
            'show_on_website' => true,
        ]);

        $this->assertDatabaseHas('knowledge_base_articles', [
            'title' => 'Autumn Fair',
            'ai_generation_key' => 'autumn-fair-2027',
            'show_on_website' => false,
        ]);
    }

    public function test_ai_generation_is_idempotent_across_runs(): void
    {
        $this->seed(SettingsSeeder::class);
        Setting::query()->where('key', 'openai_api_key')->update(['value' => Setting::encryptSecret('sk-test-openai')]);
        cache()->forget('settings.all');

        Property::factory()->create();

        Http::fake([
            'api.openai.com/*' => Http::response([
                'output_text' => json_encode([
                    'summary' => 'Regular annual events.',
                    'events' => [
                        [
                            'generation_key' => 'village-fete',
                            'title' => 'Village Fete',
                            'category' => 'festival',
                            'location' => 'Village green',
                            'description' => 'Annual summer fete.',
                            'start_date' => now()->addMonth()->toDateString(),
                            'end_date' => now()->addMonth()->addDay()->toDateString(),
                            'priority' => 4,
                            'show_on_website' => true,
                        ],
                    ],
                ], JSON_THROW_ON_ERROR),
            ], 200),
        ]);

        $this->actingAs($this->adminUser())->post(route('admin.events.ai.generate'))->assertRedirect();
        $this->actingAs($this->adminUser())->post(route('admin.events.ai.generate'))->assertRedirect();

        $this->assertSame(1, KnowledgeBaseArticle::query()->where('ai_generation_key', 'village-fete')->count());
    }

    public function test_generate_falls_back_to_bank_holidays_when_ai_provider_unconfigured(): void
    {
        Property::factory()->create();

        $this->actingAs($this->adminUser())
            ->post(route('admin.events.ai.generate'))
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('knowledge_base_articles', [
            'title' => 'Christmas Day',
            'category' => 'event',
            'source' => 'ai',
        ]);
    }

    private function adminUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findByName('Super Admin'));

        return $user;
    }
}
