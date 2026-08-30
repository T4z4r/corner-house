<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_dashboard_requires_authentication(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public function test_user_without_permission_cannot_view_settings(): void
    {
        $role = Role::findByName('Support Staff');
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user)
            ->get(route('admin.settings'))
            ->assertForbidden();
    }

    public function test_user_with_settings_permission_can_view_settings(): void
    {
        $role = Role::create(['name' => 'Settings Admin', 'guard_name' => 'web']);
        $role->givePermissionTo('settings.view');
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user)
            ->get(route('admin.settings'))
            ->assertOk();
    }

    public function test_super_admin_can_access_dashboard(): void
    {
        $role = Role::findByName('Super Admin');
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Sign out')
            ->assertSee('data-chat-widget', false)
            ->assertSee('data-source="admin"', false)
            ->assertSee('data-open-chat-widget', false);
    }
}
