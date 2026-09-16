<?php

namespace Tests\Feature;

use App\Mail\SystemNotificationMail;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\User;
use App\Services\Notification\HostNotificationRecipients;
use App\Services\Notification\SystemNotificationService;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class HostNotificationRecipientsTest extends TestCase
{
    use RefreshDatabase;

    public function test_host_email_lists_can_be_saved_and_invalid_addresses_are_rejected(): void
    {
        $this->seed([RoleAndPermissionSeeder::class, SettingsSeeder::class]);
        $user = User::factory()->create();
        $user->assignRole('Super Admin');
        $this->actingAs($user)->withConfirmedPassword();
        $list = "owner@example.com; manager@example.com\nOWNER@example.com";
        $this->put(route('admin.settings.update'), ['admin_notification_email' => $list, 'booking_notify_email' => $list])
            ->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame(['owner@example.com', 'manager@example.com'], HostNotificationRecipients::parse(Setting::getValue('admin_notification_email')));
        $this->get(route('admin.settings.notifications'))->assertOk()->assertSee('textarea', false)->assertSee('semicolons');
        $this->put(route('admin.settings.update'), ['admin_notification_email' => 'owner@example.com, invalid', 'booking_notify_email' => 'invalid'])
            ->assertSessionHasErrors(['admin_notification_email', 'booking_notify_email']);
        $this->assertSame($list, Setting::getValue('admin_notification_email'));
    }

    public function test_payment_notifications_reach_all_hosts_once(): void
    {
        Mail::fake();
        Setting::updateOrCreate(['key' => 'admin_notification_email'], ['value' => 'owner@example.com, manager@example.com; OWNER@example.com']);
        $payment = Payment::factory()->paid()->create();
        app(SystemNotificationService::class)->paymentMarkedPaid($payment);
        Mail::assertSent(SystemNotificationMail::class, fn ($mail): bool => $mail->hasTo('owner@example.com') && $mail->hasTo('manager@example.com') && count($mail->to) === 2);
    }
}
