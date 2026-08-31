<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['group' => 'general', 'key' => 'property_name', 'value' => 'Corner House', 'label' => 'Property / Business Name', 'cast' => 'string'],
            ['group' => 'general', 'key' => 'currency', 'value' => 'GBP', 'label' => 'Currency', 'cast' => 'string'],
            ['group' => 'mail', 'key' => 'mail_mailer', 'value' => 'smtp', 'label' => 'Default mailer', 'cast' => 'string'],
            ['group' => 'mail', 'key' => 'mail_host', 'value' => '127.0.0.1', 'label' => 'SMTP host', 'cast' => 'string'],
            ['group' => 'mail', 'key' => 'mail_port', 'value' => '2525', 'label' => 'SMTP port', 'cast' => 'integer'],
            ['group' => 'mail', 'key' => 'mail_username', 'value' => '', 'label' => 'SMTP username', 'cast' => 'string'],
            ['group' => 'mail', 'key' => 'mail_password', 'value' => '', 'label' => 'SMTP password', 'cast' => 'secret'],
            ['group' => 'mail', 'key' => 'mail_encryption', 'value' => '', 'label' => 'SMTP encryption', 'cast' => 'string'],
            ['group' => 'mail', 'key' => 'mail_log_channel', 'value' => 'stack', 'label' => 'Log mail channel', 'cast' => 'string'],
            ['group' => 'mail', 'key' => 'mail_from_address', 'value' => 'hello@example.com', 'label' => 'From address', 'cast' => 'string'],
            ['group' => 'mail', 'key' => 'mail_from_name', 'value' => 'Corner House', 'label' => 'From name', 'cast' => 'string'],
            ['group' => 'communication', 'key' => 'guest_checkin_time', 'value' => '15:00', 'label' => 'Standard check-in time', 'cast' => 'string'],
            ['group' => 'communication', 'key' => 'guest_checkout_time', 'value' => '12:00', 'label' => 'Standard check-out time', 'cast' => 'string'],
            ['group' => 'booking', 'key' => 'booking_hold_minutes', 'value' => '15', 'label' => 'Booking hold duration (minutes)', 'cast' => 'integer'],
            ['group' => 'booking', 'key' => 'min_advance_days', 'value' => '2', 'label' => 'Minimum advance booking (days)', 'cast' => 'integer'],
            ['group' => 'booking', 'key' => 'min_stay_nights', 'value' => '2', 'label' => 'Minimum stay (nights)', 'cast' => 'integer'],
            ['group' => 'booking', 'key' => 'min_stay_bank_holiday_nights', 'value' => '3', 'label' => 'Minimum stay on bank holiday weekends (nights)', 'cast' => 'integer'],
            ['group' => 'booking', 'key' => 'cleaning_fee', 'value' => '50', 'label' => 'Cleaning fee (£)', 'cast' => 'decimal:2'],
            ['group' => 'booking', 'key' => 'damage_deposit', 'value' => '950', 'label' => 'Damage deposit for direct bookings (£)', 'cast' => 'decimal:2'],
            ['group' => 'booking', 'key' => 'direct_booking_discount', 'value' => '10', 'label' => 'Direct booking discount (%)', 'cast' => 'integer'],
            ['group' => 'booking', 'key' => 'max_adults', 'value' => '12', 'label' => 'Max adults', 'cast' => 'integer'],
            ['group' => 'booking', 'key' => 'max_infants', 'value' => '2', 'label' => 'Max infants (age 6 and under)', 'cast' => 'integer'],
            ['group' => 'booking', 'key' => 'max_cots', 'value' => '2', 'label' => 'Max cots (babies)', 'cast' => 'integer'],
            ['group' => 'booking', 'key' => 'auto_approve_guests', 'value' => '1', 'label' => 'Auto-approve returning guests with history', 'cast' => 'boolean'],
            ['group' => 'booking', 'key' => 'min_price_weekday', 'value' => '450', 'label' => 'Minimum price - weekday (£/night)', 'cast' => 'decimal:2'],
            ['group' => 'booking', 'key' => 'min_price_weekend', 'value' => '600', 'label' => 'Minimum price - weekend (£/night)', 'cast' => 'decimal:2'],
            ['group' => 'notifications', 'key' => 'email_notifications_enabled', 'value' => '1', 'label' => 'Enable email notifications', 'cast' => 'boolean'],
            ['group' => 'notifications', 'key' => 'email_booking_confirmation_enabled', 'value' => '1', 'label' => 'Booking confirmation emails', 'cast' => 'boolean'],
            ['group' => 'notifications', 'key' => 'email_payment_confirmation_enabled', 'value' => '1', 'label' => 'Payment confirmation emails', 'cast' => 'boolean'],
            ['group' => 'notifications', 'key' => 'email_pre_arrival_enabled', 'value' => '1', 'label' => 'Pre-arrival emails', 'cast' => 'boolean'],
            ['group' => 'notifications', 'key' => 'email_check_in_enabled', 'value' => '1', 'label' => 'Check-in emails', 'cast' => 'boolean'],
            ['group' => 'notifications', 'key' => 'email_check_out_enabled', 'value' => '1', 'label' => 'Check-out emails', 'cast' => 'boolean'],
            ['group' => 'ai', 'key' => 'ai_provider', 'value' => 'openai', 'label' => 'AI provider', 'cast' => 'string'],
            ['group' => 'ai', 'key' => 'openai_api_key', 'value' => '', 'label' => 'OpenAI API key', 'cast' => 'secret'],
            ['group' => 'ai', 'key' => 'openai_model', 'value' => 'gpt-4o-mini', 'label' => 'OpenAI model', 'cast' => 'string'],
            ['group' => 'ai', 'key' => 'claude_api_key', 'value' => '', 'label' => 'Claude API key', 'cast' => 'secret'],
            ['group' => 'ai', 'key' => 'claude_model', 'value' => 'claude-sonnet-4-5', 'label' => 'Claude model', 'cast' => 'string'],
            ['group' => 'ai', 'key' => 'ai_auto_respond', 'value' => '1', 'label' => 'Auto-respond in website chat', 'cast' => 'boolean'],
            ['group' => 'ai', 'key' => 'ai_auto_respond_messages', 'value' => '1', 'label' => 'Auto-respond to guest messages', 'cast' => 'boolean'],
            ['group' => 'ai', 'key' => 'ai_instructions', 'value' => 'You are the Corner House guest assistant. Only use the provided facts. Never invent availability, prices, payment status, or reservations. Never reveal credentials or internal system details.', 'label' => 'AI instructions', 'cast' => 'string'],
            ['group' => 'website', 'key' => 'website_tagline', 'value' => 'A luxury countryside retreat', 'label' => 'Tagline', 'cast' => 'string'],
            ['group' => 'website', 'key' => 'website_hero_headline', 'value' => 'Welcome to Corner House', 'label' => 'Hero headline', 'cast' => 'string'],
            ['group' => 'website', 'key' => 'website_hero_subtitle', 'value' => 'Your perfect countryside escape awaits', 'label' => 'Hero subtitle', 'cast' => 'string'],
            ['group' => 'website', 'key' => 'website_contact_email', 'value' => '', 'label' => 'Contact email', 'cast' => 'string'],
            ['group' => 'website', 'key' => 'website_contact_phone', 'value' => '', 'label' => 'Contact phone', 'cast' => 'string'],
            ['group' => 'website', 'key' => 'website_address', 'value' => '', 'label' => 'Address', 'cast' => 'string'],
            ['group' => 'website', 'key' => 'website_facebook', 'value' => '', 'label' => 'Facebook URL', 'cast' => 'string'],
            ['group' => 'website', 'key' => 'website_instagram', 'value' => '', 'label' => 'Instagram URL', 'cast' => 'string'],
            ['group' => 'website', 'key' => 'website_twitter', 'value' => '', 'label' => 'Twitter / X URL', 'cast' => 'string'],
            ['group' => 'website', 'key' => 'website_youtube', 'value' => '', 'label' => 'YouTube URL', 'cast' => 'string'],
            ['group' => 'website', 'key' => 'website_tiktok', 'value' => '', 'label' => 'TikTok URL', 'cast' => 'string'],
            ['group' => 'website', 'key' => 'website_footer_text', 'value' => '', 'label' => 'Footer text', 'cast' => 'string'],
            ['group' => 'website', 'key' => 'website_about_text', 'value' => '', 'label' => 'About page text', 'cast' => 'string'],
            ['group' => 'website', 'key' => 'website_logo', 'value' => '', 'label' => 'Logo', 'cast' => 'string'],
            ['group' => 'website', 'key' => 'website_favicon', 'value' => '', 'label' => 'Favicon', 'cast' => 'string'],
            ['group' => 'website', 'key' => 'website_hero_image', 'value' => '', 'label' => 'Hero background image', 'cast' => 'string'],
            ['group' => 'website', 'key' => 'website_hero_gallery_main', 'value' => '', 'label' => 'Hero gallery — large image', 'cast' => 'string'],
            ['group' => 'website', 'key' => 'website_hero_gallery_small', 'value' => '', 'label' => 'Hero gallery — small image', 'cast' => 'string'],
            ['group' => 'website', 'key' => 'hero_bedrooms', 'value' => '', 'label' => 'Hero — Bedrooms value', 'cast' => 'string'],
            ['group' => 'website', 'key' => 'hero_bathrooms', 'value' => '', 'label' => 'Hero — Bathrooms value', 'cast' => 'string'],
            ['group' => 'website', 'key' => 'hero_guests', 'value' => '', 'label' => 'Hero — Guests value', 'cast' => 'string'],
            ['group' => 'website', 'key' => 'hero_rooms', 'value' => '', 'label' => 'Hero — Rooms value', 'cast' => 'string'],
            ['group' => 'website', 'key' => 'website_about_image', 'value' => '', 'label' => 'About page image', 'cast' => 'string'],
            ['group' => 'website', 'key' => 'website_og_image', 'value' => '', 'label' => 'Social sharing image (OG)', 'cast' => 'string'],
            ['group' => 'website', 'key' => 'platform_airbnb_url', 'value' => '', 'label' => 'Airbnb listing URL', 'cast' => 'string'],
            ['group' => 'website', 'key' => 'platform_booking_url', 'value' => '', 'label' => 'Booking.com listing URL', 'cast' => 'string'],
            ['group' => 'website', 'key' => 'platform_vrbo_url', 'value' => '', 'label' => 'VRBO listing URL', 'cast' => 'string'],
            ['group' => 'stripe', 'key' => 'stripe_key', 'value' => '', 'label' => 'Stripe publishable key', 'cast' => 'secret'],
            ['group' => 'stripe', 'key' => 'stripe_secret', 'value' => '', 'label' => 'Stripe secret key', 'cast' => 'secret'],
            ['group' => 'stripe', 'key' => 'stripe_webhook_secret', 'value' => '', 'label' => 'Stripe webhook signing secret', 'cast' => 'secret'],
            ['group' => 'stripe', 'key' => 'stripe_test_mode', 'value' => '1', 'label' => 'Test mode (use Stripe test keys)', 'cast' => 'boolean'],
        ];

        foreach ($defaults as $setting) {
            Setting::firstOrCreate(['key' => $setting['key']], $setting);
        }
    }
}
