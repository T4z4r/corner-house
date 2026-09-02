<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_guest_is_redirected_to_login_for_account_page(): void
    {
        $this->get(route('account.show'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_account_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('account.show'))
            ->assertOk()
            ->assertSee('My account')
            ->assertSee('Change password')
            ->assertSee($user->name);
    }

    public function test_user_can_update_their_profile_details(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('account.profile.update'), [
                'name' => 'New Name',
                'email' => 'new@example.com',
            ])->assertRedirect()
            ->assertSessionHas('status', 'Account details updated.');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Name',
            'email' => 'new@example.com',
        ]);
    }

    public function test_user_can_not_use_another_users_email(): void
    {
        $other = User::factory()->create(['email' => 'other@example.com']);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('account.profile.update'), [
                'name' => $user->name,
                'email' => 'other@example.com',
            ])->assertSessionHasErrors('email');
    }

    public function test_user_can_change_their_password(): void
    {
        $user = User::factory()->create(['password' => bcrypt('old-password')]);

        $this->actingAs($user)
            ->put(route('account.password.update'), [
                'current_password' => 'old-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])->assertRedirect()
            ->assertSessionHas('status', 'Password updated.');

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
    }

    public function test_password_change_fails_with_wrong_current_password(): void
    {
        $user = User::factory()->create(['password' => bcrypt('old-password')]);

        $this->actingAs($user)
            ->put(route('account.password.update'), [
                'current_password' => 'wrong-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }

    public function test_password_change_fails_with_short_password(): void
    {
        $user = User::factory()->create(['password' => bcrypt('old-password')]);

        $this->actingAs($user)
            ->put(route('account.password.update'), [
                'current_password' => 'old-password',
                'password' => 'short',
                'password_confirmation' => 'short',
            ])->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }

    public function test_password_change_requires_matching_confirmation(): void
    {
        $user = User::factory()->create(['password' => bcrypt('old-password')]);

        $this->actingAs($user)
            ->put(route('account.password.update'), [
                'current_password' => 'old-password',
                'password' => 'new-password',
                'password_confirmation' => 'different-password',
            ])->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }
}
