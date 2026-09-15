<?php

namespace Tests\Feature;

use App\Mail\GuestCommunicationMail;
use App\Mail\SystemNotificationMail;
use App\Models\Communication;
use App\Models\CommunicationTemplate;
use App\Models\Reservation;
use App\Models\Setting;
use App\Models\User;
use App\Services\Cron\CronRunRecorder;
use App\Services\Notification\SystemNotificationService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminNotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_feed_returns_recent_notifications_and_unread_count(): void
    {
        $user = $this->adminUser('Primary Admin');
        $actor = $this->adminUser('Actor Admin');
        $reservation = Reservation::factory()->create(['status' => 'confirmed']);

        app(SystemNotificationService::class)->reservationCreated($reservation, $actor->id);

        $response = $this->actingAs($user)->getJson(route('admin.notifications.feed'));

        $response->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonPath('notifications.0.title', 'Reservation confirmed')
            ->assertJsonPath('notifications.0.url', route('admin.reservations.show', $reservation, false))
            ->assertJsonPath('all_read_url', route('admin.notifications.mark-all-read', [], false))
            ->assertJsonPath('index_url', route('admin.notifications.index', [], false))
            ->assertJsonStructure([
                'unread_count',
                'latest_id',
                'notifications' => [
                    ['id', 'title', 'message', 'url', 'level', 'icon', 'read_at', 'created_at', 'diff_for_humans'],
                ],
            ]);
    }

    public function test_marking_a_notification_as_read_updates_the_feed(): void
    {
        $user = $this->adminUser('Primary Admin');
        $reservation = Reservation::factory()->create(['status' => 'confirmed']);

        app(SystemNotificationService::class)->reservationCreated($reservation);

        $notificationId = $user->notifications()->firstOrFail()->id;

        $this->actingAs($user)
            ->postJson(route('admin.notifications.read', $notificationId))
            ->assertOk();

        $this->assertNotNull($user->notifications()->firstOrFail()->read_at);

        $this->actingAs($user)
            ->getJson(route('admin.notifications.feed'))
            ->assertJsonPath('unread_count', 0);
    }

    public function test_system_notifications_are_broadcast_to_all_admin_users_except_the_actor(): void
    {
        $actor = $this->adminUser('Actor');
        $recipient = $this->adminUser('Recipient');
        $reservation = Reservation::factory()->create(['status' => 'confirmed']);

        app(SystemNotificationService::class)->reservationCreated($reservation, $actor->id);

        $this->assertDatabaseCount('notifications', 1);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $recipient->id,
            'type' => 'App\\Notifications\\SystemNotification',
        ]);
        $this->assertDatabaseMissing('notifications', [
            'notifiable_id' => $actor->id,
            'type' => 'App\\Notifications\\SystemNotification',
        ]);
    }

    public function test_sending_a_manual_message_creates_a_system_notification(): void
    {
        Mail::fake();

        $actor = $this->adminUser('Sender');
        $recipient = $this->adminUser('Receiver');

        $this->actingAs($actor)
            ->post(route('admin.communications.send'), [
                'recipient' => 'guest@example.test',
                'subject' => 'Guest update',
                'body' => 'Your stay has been updated.',
                'channel' => 'email',
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('notifications', 1);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $recipient->id,
            'type' => 'App\\Notifications\\SystemNotification',
        ]);
    }

    public function test_retrying_a_failed_communication_marks_it_sent(): void
    {
        Mail::fake();

        $actor = $this->adminUser('Sender');
        $recipient = $this->adminUser('Receiver');

        $communication = Communication::factory()->create([
            'channel' => 'email',
            'status' => 'failed',
            'error_message' => 'Connection refused',
            'recipient' => 'guest@example.test',
            'subject' => 'Guest update',
            'body' => 'Your stay has been updated.',
        ]);

        $this->actingAs($actor)
            ->post(route('admin.communications.retry', $communication))
            ->assertRedirect();

        $this->assertDatabaseHas('communications', [
            'id' => $communication->id,
            'status' => 'sent',
            'error_message' => null,
        ]);
        Mail::assertSent(GuestCommunicationMail::class, 1);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $recipient->id,
            'type' => 'App\\Notifications\\SystemNotification',
        ]);
    }

    public function test_retry_is_rejected_for_a_successfully_sent_communication(): void
    {
        Mail::fake();

        $actor = $this->adminUser('Sender');

        $communication = Communication::factory()->create([
            'channel' => 'email',
            'status' => 'sent',
            'recipient' => 'guest@example.test',
            'subject' => 'Guest update',
            'body' => 'Already delivered.',
        ]);

        $this->actingAs($actor)
            ->post(route('admin.communications.retry', $communication))
            ->assertRedirect()
            ->assertSessionHas('status', 'Only failed messages can be retried.');

        $this->assertDatabaseHas('communications', [
            'id' => $communication->id,
            'status' => 'sent',
        ]);
        Mail::assertNothingSent();
    }

    public function test_retrying_without_send_permission_is_forbidden(): void
    {
        $communication = Communication::factory()->create([
            'channel' => 'email',
            'status' => 'failed',
        ]);

        $this->actingAs(User::factory()->create())
            ->post(route('admin.communications.retry', $communication))
            ->assertForbidden();

        $this->assertDatabaseHas('communications', [
            'id' => $communication->id,
            'status' => 'failed',
        ]);
    }

    public function test_editing_a_failed_communication_updates_its_fields(): void
    {
        Mail::fake();

        $actor = $this->adminUser('Sender');

        $communication = Communication::factory()->create([
            'channel' => 'email',
            'status' => 'failed',
            'error_message' => 'Connection refused',
            'recipient' => 'guest@example.test',
            'subject' => 'Original subject',
            'body' => 'Original body.',
        ]);

        $this->actingAs($actor)
            ->put(route('admin.communications.update', $communication), [
                'recipient' => 'new@example.test',
                'subject' => 'Updated subject',
                'body' => 'Updated body.',
                'channel' => 'email',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('communications', [
            'id' => $communication->id,
            'recipient' => 'new@example.test',
            'subject' => 'Updated subject',
            'body' => 'Updated body.',
            'status' => 'failed',
        ]);
        Mail::assertNothingSent();
    }

    public function test_editing_a_sent_communication_is_rejected(): void
    {
        $actor = $this->adminUser('Sender');

        $communication = Communication::factory()->create([
            'channel' => 'email',
            'status' => 'sent',
            'recipient' => 'guest@example.test',
            'subject' => 'Sent subject',
            'body' => 'Sent body.',
        ]);

        $this->actingAs($actor)
            ->put(route('admin.communications.update', $communication), [
                'recipient' => 'new@example.test',
                'subject' => 'Changed subject',
                'body' => 'Changed body.',
                'channel' => 'email',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Only undelivered email messages can be edited.');

        $this->assertDatabaseHas('communications', [
            'id' => $communication->id,
            'subject' => 'Sent subject',
        ]);
    }

    public function test_resending_a_sent_communication_creates_a_new_delivery(): void
    {
        Mail::fake();

        $actor = $this->adminUser('Sender');
        $recipient = $this->adminUser('Receiver');

        $original = Communication::factory()->create([
            'channel' => 'email',
            'status' => 'sent',
            'recipient' => 'guest@example.test',
            'subject' => 'Guest update',
            'body' => 'Your stay has been updated.',
            'sent_at' => now()->subDay(),
        ]);

        $this->actingAs($actor)
            ->post(route('admin.communications.resend', $original))
            ->assertRedirect();

        $this->assertDatabaseCount('communications', 2);
        $resend = Communication::query()->whereKeyNot($original->id)->firstOrFail();
        $this->assertSame('sent', $resend->status);
        $this->assertSame('guest@example.test', $resend->recipient);
        $this->assertSame('Guest update', $resend->subject);
        $this->assertSame('Your stay has been updated.', $resend->body);
        Mail::assertSent(GuestCommunicationMail::class, 1);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $recipient->id,
            'type' => 'App\\Notifications\\SystemNotification',
        ]);
    }

    public function test_resending_a_failed_communication_is_rejected(): void
    {
        Mail::fake();

        $actor = $this->adminUser('Sender');

        $communication = Communication::factory()->create([
            'channel' => 'email',
            'status' => 'failed',
            'error_message' => 'Connection refused',
        ]);

        $this->actingAs($actor)
            ->post(route('admin.communications.resend', $communication))
            ->assertRedirect()
            ->assertSessionHas('status', 'Only sent email messages can be resent.');

        $this->assertDatabaseCount('communications', 1);
        Mail::assertNothingSent();
    }

    public function test_deleting_a_communication_removes_it_from_history(): void
    {
        $actor = $this->adminUser('Sender');

        $communication = Communication::factory()->create([
            'channel' => 'email',
            'status' => 'sent',
            'recipient' => 'guest@example.test',
            'subject' => 'Guest update',
        ]);

        $this->actingAs($actor)
            ->delete(route('admin.communications.destroy', $communication))
            ->assertRedirect(route('admin.communications.index'));

        $this->assertDatabaseMissing('communications', ['id' => $communication->id]);
    }

    public function test_communication_actions_without_send_permission_are_forbidden(): void
    {
        $communication = Communication::factory()->create([
            'channel' => 'email',
            'status' => 'sent',
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('admin.communications.update', $communication), [
                'recipient' => 'new@example.test',
                'subject' => 'Changed subject',
                'body' => 'Changed body.',
                'channel' => 'email',
            ])
            ->assertForbidden();
        $this->actingAs($user)
            ->post(route('admin.communications.resend', $communication))
            ->assertForbidden();
        $this->actingAs($user)
            ->delete(route('admin.communications.destroy', $communication))
            ->assertForbidden();

        $this->assertDatabaseCount('communications', 1);
    }

    public function test_system_notifications_send_email_to_admin_notification_email(): void
    {
        Mail::fake();

        Setting::updateOrCreate(['key' => 'admin_notification_email'], ['group' => 'notifications', 'value' => 'admin-recipient@example.com', 'cast' => 'string']);
        Setting::updateOrCreate(['key' => 'email_notifications_enabled'], ['group' => 'notifications', 'value' => '1', 'cast' => 'boolean']);

        $reservation = Reservation::factory()->create(['status' => 'confirmed']);

        app(SystemNotificationService::class)->reservationCreated($reservation);

        Mail::assertSent(SystemNotificationMail::class, function (SystemNotificationMail $mail): bool {
            return $mail->hasTo('admin-recipient@example.com')
                && str_contains($mail->envelope()->subject, 'Reservation confirmed');
        });
    }

    public function test_cron_job_runs_do_not_send_system_notification_emails(): void
    {
        Mail::fake();

        Setting::updateOrCreate(['key' => 'admin_notification_email'], ['group' => 'notifications', 'value' => 'admin-recipient@example.com', 'cast' => 'string']);

        $run = CronRunRecorder::start('GenerateSeasonalPricingJob');
        CronRunRecorder::finish($run);

        Mail::assertNothingSent();
    }

    public function test_sending_a_template_test_email_renders_reservation_tokens(): void
    {
        Mail::fake();

        $actor = $this->adminUser('Sender');
        $reservation = Reservation::factory()->create([
            'total_amount' => 1250.50,
        ]);
        $template = CommunicationTemplate::factory()->create([
            'subject' => 'Booking {{reference}} — thank you {{guest_name}}',
            'body' => 'Hi {{guest_name}}, your stay {{check_in}} to {{check_out}} ({{nights}} nights) is confirmed. Total: £{{total}}.',
        ]);

        $this->actingAs($actor)
            ->post(route('admin.communications.templates.test', $template), [
                'recipient' => 'admin-test@example.com',
                'reservation_id' => $reservation->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('status', "Test '{$template->name}' sent.");

        $this->assertDatabaseHas('communications', [
            'communication_template_id' => $template->id,
            'reservation_id' => $reservation->id,
            'channel' => 'email',
            'recipient' => 'admin-test@example.com',
            'status' => 'sent',
            'metadata' => json_encode(['source' => 'test']),
        ]);

        Mail::assertSent(GuestCommunicationMail::class, 1);
        $mail = Mail::sent(GuestCommunicationMail::class)->first();
        $this->assertTrue($mail->hasTo('admin-test@example.com'));
        $rendered = $mail->render();
        $this->assertStringContainsString($reservation->guest->full_name, $rendered);
        $this->assertStringContainsString('£1,250.50', $rendered);
    }

    public function test_sending_a_template_test_without_reservation_uses_sample_values(): void
    {
        Mail::fake();

        $actor = $this->adminUser('Sender');
        $template = CommunicationTemplate::factory()->create([
            'body' => 'Hello {{guest_name}}, booking {{reference}} for {{property}}.',
        ]);

        $this->actingAs($actor)
            ->post(route('admin.communications.templates.test', $template), [
                'recipient' => 'admin-test@example.com',
            ])
            ->assertRedirect();

        $mail = Mail::sent(GuestCommunicationMail::class)->first();
        $rendered = $mail->render();
        $this->assertStringContainsString('Hello Sample Guest, booking CH-000001 for Corner House.', $rendered);
    }

    public function test_testing_a_template_without_send_permission_is_forbidden(): void
    {
        $template = CommunicationTemplate::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('admin.communications.templates.test', $template), [
                'recipient' => 'admin-test@example.com',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('communications', 0);
    }

    private function adminUser(string $name = 'Admin'): User
    {
        $user = User::factory()->create(['name' => $name]);
        $user->assignRole(Role::findByName('Super Admin'));

        return $user;
    }
}
