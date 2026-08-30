<?php

namespace Tests\Feature;

use App\Mail\GuestCommunicationMail;
use App\Models\Communication;
use App\Models\Reservation;
use App\Models\Setting;
use App\Models\User;
use App\Services\Notification\NotificationService;
use App\Services\System\MailConfigurationService;
use Database\Seeders\CommunicationTemplateSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        $role = Role::findByName('Super Admin');
        $user = User::factory()->create();
        $user->assignRole($role);
        $this->actingAs($user);
    }

    public function test_can_view_settings_page(): void
    {
        $this->get(route('admin.settings'))->assertOk();
    }

    public function test_can_view_mail_settings_page(): void
    {
        $this->seed(SettingsSeeder::class);

        $this->get(route('admin.settings.mail'))
            ->assertOk()
            ->assertSee('Email settings')
            ->assertSee('SMTP host')
            ->assertSee('From address')
            ->assertDontSee('AI provider');
    }

    public function test_can_view_notifications_settings_page(): void
    {
        $this->seed(SettingsSeeder::class);

        $this->get(route('admin.settings.notifications'))
            ->assertOk()
            ->assertSee('Email notifications')
            ->assertSee('Enable email notifications')
            ->assertSee('Booking confirmation emails')
            ->assertDontSee('SMTP host');
    }

    public function test_can_update_existing_setting(): void
    {
        $setting = Setting::create([
            'group' => 'general',
            'key' => 'property_name',
            'value' => 'Corner House',
            'cast' => 'string',
        ]);

        $this->put(route('admin.settings.update'), [
            $setting->key => 'Corner House Updated',
        ])->assertRedirect()->assertSessionHas('status');

        $this->assertDatabaseHas('settings', [
            'key' => 'property_name',
            'value' => 'Corner House Updated',
        ]);

        $this->assertDatabaseHas('audit_logs', ['action' => 'settings.updated']);
    }

    public function test_setting_value_is_cast_correctly(): void
    {
        $setting = Setting::create([
            'group' => 'booking',
            'key' => 'booking_hold_minutes',
            'value' => '15',
            'cast' => 'integer',
        ]);

        $this->assertSame(15, $setting->castValue());
        $this->assertSame(15, Setting::getValue('booking_hold_minutes'));
    }

    public function test_get_value_returns_default_when_missing(): void
    {
        $this->assertSame('fallback', Setting::getValue('does_not_exist', 'fallback'));
    }

    public function test_can_create_new_setting(): void
    {
        $this->post(route('admin.settings.store'), [
            'group' => 'general',
            'key' => 'default_guest_count',
            'label' => 'Default guest count',
            'value' => '2',
            'cast' => 'integer',
        ])->assertRedirect()->assertSessionHas('status');

        $this->assertDatabaseHas('settings', ['key' => 'default_guest_count']);
    }

    public function test_can_save_openai_and_claude_keys_without_exposing_them(): void
    {
        $this->seed(SettingsSeeder::class);

        $this->put(route('admin.settings.update'), [
            'ai_provider' => 'claude',
            'openai_api_key' => 'sk-openai-test',
            'claude_api_key' => 'sk-claude-test',
            'openai_model' => 'gpt-4o-mini',
            'claude_model' => 'claude-sonnet-4-5',
            'ai_auto_respond' => '1',
            'ai_auto_respond_messages' => '0',
        ])->assertRedirect();

        $this->assertSame('claude', Setting::getValue('ai_provider'));
        $this->assertSame('sk-openai-test', Setting::getValue('openai_api_key'));
        $this->assertSame('sk-claude-test', Setting::getValue('claude_api_key'));
        $this->assertFalse((bool) Setting::getValue('ai_auto_respond_messages'));

        $this->get(route('admin.settings'))
            ->assertOk()
            ->assertDontSee('sk-openai-test')
            ->assertDontSee('sk-claude-test')
            ->assertSee('Saved — leave blank to keep');
    }

    public function test_blank_secret_does_not_clear_saved_key(): void
    {
        $this->seed(SettingsSeeder::class);

        $this->put(route('admin.settings.update'), [
            'openai_api_key' => 'sk-keep-me',
        ]);

        $this->put(route('admin.settings.update'), [
            'openai_api_key' => '',
        ]);

        $this->assertSame('sk-keep-me', Setting::getValue('openai_api_key'));
    }

    public function test_can_apply_mail_configuration_from_settings(): void
    {
        $this->seed(SettingsSeeder::class);

        $this->put(route('admin.settings.update'), [
            'mail_mailer' => 'log',
            'mail_host' => 'mail.example.test',
            'mail_port' => '587',
            'mail_username' => 'mailer-user',
            'mail_password' => 'secret-pass',
            'mail_encryption' => 'tls',
            'mail_log_channel' => 'custom-mail',
            'mail_from_address' => 'stays@example.test',
            'mail_from_name' => 'Corner House Mail',
        ])->assertRedirect();

        app(MailConfigurationService::class)->apply();

        $this->assertSame('log', config('mail.default'));
        $this->assertSame('mail.example.test', config('mail.mailers.smtp.host'));
        $this->assertSame(587, config('mail.mailers.smtp.port'));
        $this->assertSame('mailer-user', config('mail.mailers.smtp.username'));
        $this->assertSame('secret-pass', config('mail.mailers.smtp.password'));
        $this->assertSame('tls', config('mail.mailers.smtp.scheme'));
        $this->assertSame('custom-mail', config('mail.mailers.log.channel'));
        $this->assertSame('stays@example.test', config('mail.from.address'));
        $this->assertSame('Corner House Mail', config('mail.from.name'));
    }

    public function test_booking_email_notifications_can_be_disabled_globally(): void
    {
        $this->seed([SettingsSeeder::class, CommunicationTemplateSeeder::class]);
        Mail::fake();

        Setting::query()->where('key', 'email_notifications_enabled')->firstOrFail()->update(['value' => '0']);

        $reservation = Reservation::factory()->create();

        $result = app(NotificationService::class)->sendForEvent('booking_confirmation', $reservation);

        $this->assertNull($result);
        Mail::assertNothingSent();
        $this->assertDatabaseCount('communications', 0);
    }

    public function test_payment_notifications_can_be_disabled_for_a_specific_event(): void
    {
        $this->seed([SettingsSeeder::class, CommunicationTemplateSeeder::class]);
        Mail::fake();

        Setting::query()->where('key', 'email_payment_confirmation_enabled')->firstOrFail()->update(['value' => '0']);

        $reservation = Reservation::factory()->create();

        $result = app(NotificationService::class)->sendForEvent('payment_confirmation', $reservation);

        $this->assertNull($result);
        Mail::assertNothingSent();
        $this->assertDatabaseCount('communications', 0);
    }

    public function test_booking_confirmation_email_is_sent_when_enabled(): void
    {
        $this->seed([SettingsSeeder::class, CommunicationTemplateSeeder::class]);
        Mail::fake();

        $reservation = Reservation::factory()->create();

        $communication = app(NotificationService::class)->sendForEvent('booking_confirmation', $reservation);

        $this->assertInstanceOf(Communication::class, $communication);
        Mail::assertSent(GuestCommunicationMail::class, function (GuestCommunicationMail $mail) use ($reservation): bool {
            return $mail->hasTo($reservation->guest->email);
        });
        $this->assertDatabaseCount('communications', 1);
    }
}
