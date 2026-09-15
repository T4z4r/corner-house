<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PasswordConfirmationTest extends TestCase
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

    public function test_protected_admin_pages_redirect_to_password_confirmation(): void
    {
        foreach (['admin.calendar', 'admin.payments.index', 'admin.reservations.index', 'admin.settings'] as $route) {
            $this->actingAs($this->superAdmin())
                ->get(route($route))
                ->assertRedirect(route('password.confirm'));
        }
    }

    public function test_unprotected_admin_pages_do_not_require_confirmation(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('admin.dashboard'))
            ->assertOk();

        $this->actingAs($this->superAdmin())
            ->get(route('admin.users.index'))
            ->assertOk();
    }

    public function test_password_confirmation_page_is_displayed(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('password.confirm'))
            ->assertOk()
            ->assertSee('Confirm your password')
            ->assertSee('Unlock &amp; continue');
    }

    public function test_invalid_password_is_rejected_and_access_stays_blocked(): void
    {
        $user = $this->superAdmin();

        $this->actingAs($user)
            ->post(route('password.confirm'), ['password' => 'wrong-password'])
            ->assertSessionHasErrors('password');

        $this->assertNull(session('auth.password_confirmed_at'));

        $this->actingAs($user)
            ->get(route('admin.calendar'))
            ->assertRedirect(route('password.confirm'));
    }

    public function test_valid_password_confirms_and_returns_the_user_to_the_requested_page(): void
    {
        $user = $this->superAdmin();

        $this->actingAs($user)
            ->get(route('admin.reservations.index'))
            ->assertRedirect(route('password.confirm'));

        $this->actingAs($user)
            ->post(route('password.confirm'), ['password' => 'password'])
            ->assertRedirect(route('admin.reservations.index'));

        $this->assertNotNull(session('auth.password_confirmed_at'));

        $this->actingAs($user)
            ->get(route('admin.reservations.index'))
            ->assertOk();
    }

    public function test_confirmation_expires_after_the_password_timeout(): void
    {
        $this->actingAs($this->superAdmin())
            ->withSession(['auth.password_confirmed_at' => now()->subHours(4)->getTimestamp()])
            ->get(route('admin.settings'))
            ->assertRedirect(route('password.confirm'));

        $this->actingAs($this->superAdmin())
            ->withSession(['auth.password_confirmed_at' => now()->subMinutes(5)->getTimestamp()])
            ->get(route('admin.settings'))
            ->assertOk();
    }
}
