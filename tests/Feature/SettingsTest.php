<?php

namespace Tests\Feature;

use App\Mail\GuestCommunicationMail;
use App\Models\Communication;
use App\Models\Reservation;
use App\Models\Setting;
use App\Models\User;
use App\Services\Notification\NotificationService;
use App\Services\System\MailConfigurationService;
use App\Services\Website\WebsiteContentService;
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

    public function test_json_setting_saved_from_generic_settings_page_is_not_double_encoded(): void
    {
        $rules = json_encode([
            ['title' => 'Pricing and payment', 'items' => ['The whole house is let to one party at a time.']],
        ]);

        $setting = Setting::create([
            'group' => 'website',
            'key' => 'website_booking_rules',
            'value' => $rules,
            'cast' => 'json',
        ]);

        // The generic settings form submits the stored JSON string as-is.
        $this->put(route('admin.settings.update'), [
            $setting->key => $rules,
        ])->assertRedirect()->assertSessionHas('status');

        $fresh = Setting::find($setting->id);
        $this->assertSame($rules, $fresh->value, 'Stored value must not be double-encoded.');
        $this->assertIsArray($fresh->castValue());
        $this->assertIsArray(Setting::getValue('website_booking_rules'));
    }

    public function test_booking_rules_fall_back_to_defaults_when_setting_is_not_an_array(): void
    {
        // Simulates a previously corrupted (string) booking-rules value.
        Setting::create([
            'group' => 'website',
            'key' => 'website_booking_rules',
            'value' => '"not an array"',
            'cast' => 'json',
        ]);

        $site = app(WebsiteContentService::class)->data();

        $this->assertIsArray($site['bookingRules']);
        $this->assertSame('Pricing and payment', $site['bookingRules'][0]['title'] ?? null);
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

    public function test_website_settings_page_shows_image_upload_sections(): void
    {
        $this->seed(SettingsSeeder::class);

        $this->get(route('admin.settings.website'))
            ->assertOk()
            ->assertSee('Website settings')
            ->assertSee('dz-website_logo', false)
            ->assertSee('dz-website_footer_logo', false)
            ->assertSee('dz-website_favicon', false)
            ->assertSee('dz-website_hero_image', false)
            ->assertSee('dz-website_hero_gallery_main', false)
            ->assertSee('dz-website_hero_gallery_small', false)
            ->assertSee('dz-website_about_image', false)
            ->assertSee('dz-website_og_image', false)
            ->assertSee('dz-website_spirits_logo', false);
    }

    public function test_hero_gallery_image_setting_persists_and_renders_on_homepage(): void
    {
        $this->seed(SettingsSeeder::class);

        $this->put(route('admin.settings.update'), [
            'website_hero_gallery_main' => 'website/house-exterior.png',
            'website_hero_gallery_small' => 'website/gardens.png',
        ])->assertRedirect()->assertSessionHas('status');

        $this->assertSame('website/house-exterior.png', Setting::getValue('website_hero_gallery_main'));
        $this->assertSame('website/gardens.png', Setting::getValue('website_hero_gallery_small'));

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('storage/website/house-exterior.png', false)
            ->assertSee('storage/website/gardens.png', false);
    }
}
