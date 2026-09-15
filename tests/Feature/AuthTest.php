<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_can_render_login_page(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('Sign in');
    }

    public function test_guest_is_redirected_to_login_for_admin(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create(['password' => bcrypt('secret123')]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'secret123',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        User::factory()->create(['email' => 'john@example.com', 'password' => bcrypt('secret123')]);

        $this->post(route('login'), [
            'email' => 'john@example.com',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_public_registration_is_disabled_by_default(): void
    {
        $this->get(route('register'))->assertForbidden();

        $this->post(route('register'), [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'P@ssword123!',
            'password_confirmation' => 'P@ssword123!',
        ])->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'jane@example.com']);
        $this->assertGuest();
    }

    public function test_registration_fails_if_password_does_not_meet_strong_policy(): void
    {
        config()->set('app.allow_public_registration', true);

        $this->post(route('register'), [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'weakpass',
            'password_confirmation' => 'weakpass',
        ])->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'jane@example.com']);
    }

    public function test_login_is_rate_limited_after_repeated_failures(): void
    {
        User::factory()->create(['email' => 'john@example.com', 'password' => bcrypt('secret123')]);

        for ($i = 0; $i < 5; $i++) {
            $this->from(route('login'))->post(route('login'), [
                'email' => 'john@example.com',
                'password' => 'wrong-password',
            ]);
        }

        $this->from(route('login'))->post(route('login'), [
            'email' => 'john@example.com',
            'password' => 'wrong-password',
        ])->assertTooManyRequests();

        $this->assertGuest();
    }
}
