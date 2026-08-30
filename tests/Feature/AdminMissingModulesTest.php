<?php

namespace Tests\Feature;

use App\Models\Reservation;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminMissingModulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findByName('Super Admin'));

        return $user;
    }

    public function test_super_admin_can_open_new_admin_modules(): void
    {
        $user = $this->superAdmin();

        $this->actingAs($user)->get(route('admin.users.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.payments.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.channels.index'))->assertRedirect(route('admin.channels.integrations'));
        $this->actingAs($user)->get(route('admin.channels.integrations'))->assertOk();
        $this->actingAs($user)->get(route('admin.channels.setup.page'))->assertOk();
        $this->actingAs($user)->get(route('admin.communications.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.chatbot.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.revenue.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.reports.index'))->assertOk();
    }

    public function test_support_staff_cannot_manage_users_or_export_reports(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findByName('Support Staff'));

        $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.reports.export'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.payments.index'))->assertForbidden();
    }

    public function test_super_admin_can_create_user(): void
    {
        $this->actingAs($this->superAdmin())
            ->post(route('admin.users.store'), [
                'name' => 'Pat Manager',
                'email' => 'pat@example.com',
                'password' => 'password123',
                'role' => 'Property Manager',
            ])->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', ['email' => 'pat@example.com']);
        $this->assertTrue(User::query()->where('email', 'pat@example.com')->first()->hasRole('Property Manager'));
    }

    public function test_dashboard_shows_revenue_from_reservations(): void
    {
        Reservation::factory()->create([
            'status' => 'confirmed',
            'total_amount' => 250,
            'check_in' => now()->toDateString(),
            'check_out' => now()->addDays(2)->toDateString(),
        ]);

        $this->actingAs($this->superAdmin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('250.00');
    }

    public function test_reports_export_downloads_csv(): void
    {
        Reservation::factory()->create([
            'status' => 'confirmed',
            'reference' => 'CH-EXPORT',
            'check_in' => now()->toDateString(),
            'check_out' => now()->addDays(3)->toDateString(),
        ]);

        $response = $this->actingAs($this->superAdmin())
            ->get(route('admin.reports.export', ['type' => 'revenue']));

        $response->assertOk();
        $this->assertStringContainsString('CH-EXPORT', $response->streamedContent());
    }

    public function test_communications_template_can_be_saved(): void
    {
        $this->actingAs($this->superAdmin())
            ->post(route('admin.communications.templates.store'), [
                'name' => 'Welcome',
                'event' => 'booking_confirmation',
                'channel' => 'email',
                'subject' => 'Hello',
                'body' => 'Welcome {{guest_name}}',
                'is_active' => true,
            ])->assertRedirect();

        $this->assertDatabaseHas('communication_templates', ['name' => 'Welcome']);
    }
}
