<?php

namespace Tests\Feature;

use App\Mail\GuestCommunicationMail;
use App\Models\Guest;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GuestEmailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function makeSender(): User
    {
        $role = Role::findByName('Super Admin');
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    public function test_user_without_permission_cannot_send_email_to_guest(): void
    {
        $role = Role::create(['name' => 'Guests Only', 'guard_name' => 'web']);
        $role->givePermissionTo('guests.view');
        $user = User::factory()->create();
        $user->assignRole($role);

        $guest = Guest::factory()->create(['email' => 'guest@example.com']);

        $this->actingAs($user)
            ->post(route('admin.guests.email', $guest), [
                'subject' => 'Welcome',
                'body' => 'Hello!',
            ])
            ->assertForbidden();
    }

    public function test_guest_without_email_is_rejected(): void
    {
        $guest = Guest::factory()->create(['email' => null]);

        Mail::fake();

        $this->actingAs($this->makeSender())
            ->post(route('admin.guests.email', $guest), [
                'subject' => 'Welcome',
                'body' => 'Hello!',
            ])
            ->assertSessionHasErrors('email');

        Mail::assertNothingSent();
        $this->assertDatabaseCount('communications', 0);
    }

    public function test_email_requires_subject_and_body(): void
    {
        $guest = Guest::factory()->create(['email' => 'guest@example.com']);

        Mail::fake();

        $this->actingAs($this->makeSender())
            ->post(route('admin.guests.email', $guest), [])
            ->assertSessionHasErrors(['subject', 'body']);

        Mail::assertNothingSent();
        $this->assertDatabaseCount('communications', 0);
    }

    public function test_email_is_sent_and_recorded(): void
    {
        $guest = Guest::factory()->create(['email' => 'guest@example.com']);

        Mail::fake();

        $response = $this->actingAs($this->makeSender())
            ->from(route('admin.guests.show', $guest))
            ->post(route('admin.guests.email', $guest), [
                'subject' => 'Booking update',
                'body' => 'Your stay is confirmed.',
            ]);

        $response->assertRedirect(route('admin.guests.show', $guest));
        $response->assertSessionHas('status');

        Mail::assertSent(GuestCommunicationMail::class, 1);
        $this->assertDatabaseHas('communications', [
            'guest_id' => $guest->id,
            'channel' => 'email',
            'direction' => 'outbound',
            'recipient' => 'guest@example.com',
            'subject' => 'Booking update',
            'body' => 'Your stay is confirmed.',
            'status' => 'sent',
        ]);
    }
}
