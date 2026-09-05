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
            ['group' => 'mail', 'key' => 'mail_host', 'value' => 'mail.flex.co.tz', 'label' => 'SMTP host', 'cast' => 'string'],
            ['group' => 'mail', 'key' => 'mail_port', 'value' => '465', 'label' => 'SMTP port', 'cast' => 'integer'],
            ['group' => 'mail', 'key' => 'mail_username', 'value' => 'info@erp.flex.co.tz', 'label' => 'SMTP username', 'cast' => 'string'],
            ['group' => 'mail', 'key' => 'mail_password', 'value' => 'pjZ!k]Lp#N.j', 'label' => 'SMTP password', 'cast' => 'secret'],
            ['group' => 'mail', 'key' => 'mail_encryption', 'value' => 'ssl', 'label' => 'SMTP encryption', 'cast' => 'string'],
            ['group' => 'mail', 'key' => 'mail_log_channel', 'value' => 'stack', 'label' => 'Log mail channel', 'cast' => 'string'],
            ['group' => 'mail', 'key' => 'mail_from_address', 'value' => 'info@nextchapter.co.tz', 'label' => 'From address', 'cast' => 'string'],
            ['group' => 'mail', 'key' => 'mail_from_name', 'value' => config('app.name', 'Corner House'), 'label' => 'From name', 'cast' => 'string'],
            ['group' => 'mail', 'key' => 'mail_ssl_cafile', 'value' => 'storage/certs/balancepoint-mail-ca.pem', 'label' => 'SMTP CA certificate file', 'cast' => 'string'],
            ['group' => 'mail', 'key' => 'mail_ssl_verify_peer', 'value' => '1', 'label' => 'Verify SMTP peer certificate', 'cast' => 'boolean'],
            ['group' => 'mail', 'key' => 'mail_ssl_verify_peer_name', 'value' => '1', 'label' => 'Verify SMTP peer name', 'cast' => 'boolean'],
            ['group' => 'mail', 'key' => 'mail_ssl_allow_self_signed', 'value' => '0', 'label' => 'Allow self-signed SMTP certificates', 'cast' => 'boolean'],
            ['group' => 'communication', 'key' => 'guest_checkin_time', 'value' => '15:00', 'label' => 'Standard check-in time', 'cast' => 'string'],
            ['group' => 'communication', 'key' => 'guest_checkout_time', 'value' => '12:00', 'label' => 'Standard check-out time', 'cast' => 'string'],
            ['group' => 'booking', 'key' => 'booking_hold_minutes', 'value' => '15', 'label' => 'Booking hold duration (minutes)', 'cast' => 'integer'],
            ['group' => 'booking', 'key' => 'min_advance_days', 'value' => '1', 'label' => 'Minimum advance booking (days)', 'cast' => 'integer'],
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
            ['group' => 'pricing', 'key' => 'holiday_weekend_uplift_enabled', 'value' => '1', 'label' => 'Weekend uplift on UK holidays enabled', 'cast' => 'boolean'],
            ['group' => 'pricing', 'key' => 'holiday_weekend_uplift', 'value' => '5', 'label' => 'Weekend uplift on UK holidays (%)', 'cast' => 'integer'],
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
            ['group' => 'website', 'key' => 'website_spirits_logo', 'value' => '', 'label' => 'Serengeti Spirits logo', 'cast' => 'string'],
            ['group' => 'website', 'key' => 'platform_airbnb_url', 'value' => '', 'label' => 'Airbnb listing URL', 'cast' => 'string'],
            ['group' => 'website', 'key' => 'platform_booking_url', 'value' => '', 'label' => 'Booking.com listing URL', 'cast' => 'string'],
            ['group' => 'website', 'key' => 'platform_vrbo_url', 'value' => '', 'label' => 'VRBO listing URL', 'cast' => 'string'],
            ['group' => 'website', 'key' => 'nightly_rate', 'value' => '950', 'label' => 'Whole-house rate (£/night)', 'cast' => 'decimal:2'],
            ['group' => 'website', 'key' => 'website_months_ahead', 'value' => '18', 'label' => 'Availability calendar — months ahead', 'cast' => 'integer'],
            ['group' => 'website', 'key' => 'hero_square_feet', 'value' => '4,000', 'label' => 'Hero — Square feet value', 'cast' => 'string'],
            ['group' => 'website', 'key' => 'hero_kitchen', 'value' => '25 ft', 'label' => 'Hero — Kitchen value', 'cast' => 'string'],
            ['group' => 'website', 'key' => 'hero_built', 'value' => '1850', 'label' => 'Hero — Year built value', 'cast' => 'string'],
            ['group' => 'website', 'key' => 'review_score', 'value' => '4.95', 'label' => 'Average review score', 'cast' => 'string'],
            ['group' => 'website', 'key' => 'review_count', 'value' => '40', 'label' => 'Number of Airbnb reviews', 'cast' => 'integer'],
            ['group' => 'website', 'key' => 'website_footer_logo', 'value' => '', 'label' => 'Footer logo', 'cast' => 'string'],
            ['group' => 'website', 'key' => 'website_footer_capacity', 'value' => 'Sleeps 12 adults and 2 children in five ensuite bedrooms.', 'label' => 'Footer — capacity line', 'cast' => 'string'],
            ['group' => 'website', 'key' => 'spirits_website', 'value' => 'https://www.serengetispirits.com', 'label' => 'Serengeti Spirits website', 'cast' => 'string'],
            ['group' => 'website', 'key' => 'website_video_url', 'value' => '', 'label' => 'Video tour URL (YouTube/Vimeo embed or MP4)', 'cast' => 'string'],
            ['group' => 'website', 'key' => 'website_blocked_dates', 'value' => '[]', 'label' => 'Blocked dates (ISO YYYY-MM-DD)', 'cast' => 'json'],
            ['group' => 'website', 'key' => 'website_spaces_inside', 'value' => json_encode(SettingsSeeder::defaultInsideSpaces()), 'label' => 'Rooms — inside spaces', 'cast' => 'json'],
            ['group' => 'website', 'key' => 'website_spaces_outside', 'value' => json_encode(SettingsSeeder::defaultOutsideSpaces()), 'label' => 'Rooms — outside spaces', 'cast' => 'json'],
            ['group' => 'website', 'key' => 'website_walking_routes', 'value' => json_encode(SettingsSeeder::defaultWalks()), 'label' => 'Places — walking routes', 'cast' => 'json'],
            ['group' => 'website', 'key' => 'website_booking_rules', 'value' => json_encode(SettingsSeeder::defaultBookingRules()), 'label' => 'Booking — booking rules', 'cast' => 'json'],
            ['group' => 'website', 'key' => 'website_house_rules', 'value' => json_encode(SettingsSeeder::defaultHouseRules()), 'label' => 'Booking — house rules', 'cast' => 'json'],
            ['group' => 'stripe', 'key' => 'stripe_key', 'value' => '', 'label' => 'Stripe publishable key', 'cast' => 'secret'],
            ['group' => 'stripe', 'key' => 'stripe_secret', 'value' => '', 'label' => 'Stripe secret key', 'cast' => 'secret'],
            ['group' => 'stripe', 'key' => 'stripe_webhook_secret', 'value' => '', 'label' => 'Stripe webhook signing secret', 'cast' => 'secret'],
            ['group' => 'stripe', 'key' => 'stripe_test_mode', 'value' => '1', 'label' => 'Test mode (use Stripe test keys)', 'cast' => 'boolean'],
        ];

        foreach ($defaults as $setting) {
            if ($setting['group'] === 'mail') {
                Setting::query()->updateOrCreate(['key' => $setting['key']], $setting);

                continue;
            }

            Setting::firstOrCreate(['key' => $setting['key']], $setting);
        }
    }

    /**
     * @return array<int, array<string, string>>
     */
    public static function defaultInsideSpaces(): array
    {
        return [
            ['name' => 'The kitchen', 'where' => 'Ground floor', 'description' => 'The 25-foot centrepiece of the house, and the reason it works so well for a full party. Everyone ends up here, so there is room for everyone to be here.', 'label' => 'The kitchen photo', 'feature' => '1'],
            ['name' => 'Garden dining room', 'where' => 'Ground floor, the old orangery', 'description' => 'The orangery, converted into a dining room that seats ten at a handmade farmhouse table and chairs, with the garden on three sides.', 'label' => 'Garden dining room photo'],
            ['name' => 'Lounge', 'where' => 'Ground floor', 'description' => 'The lounge is a calm space on the ground floor with seating, a fireplace and a television, flowing into the kitchen and the garden dining room.', 'label' => 'Lounge photo'],
            ['name' => 'Games room', 'where' => 'Ground floor', 'description' => 'A dedicated room for the games, with a pool table, darts, board games and a console.', 'label' => 'Games room photo'],
            ['name' => 'Cinema room', 'where' => 'The converted cellar', 'description' => 'The cellar has been converted into a cinema room with a projector screen and comfortable seating for the whole party.', 'label' => 'Cinema room photo'],
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    public static function defaultOutsideSpaces(): array
    {
        return [
            ['name' => 'Entertaining patio and garden bar', 'where' => 'The garden', 'description' => 'The patio is where the house spills out on a warm evening: the garden bar, the Kadai fire-pit barbecue and the hot tub, with the landscaped garden beyond.', 'label' => 'Entertaining patio and garden bar photo', 'feature' => '1'],
            ['name' => 'Hot tub', 'where' => 'On the patio', 'description' => 'Sits on the patio, a few steps from the garden bar, and is available between 8:00am and 11:00pm.', 'label' => 'Hot tub photo'],
            ['name' => 'Garden room', 'where' => 'The garden', 'description' => 'A quiet spot in the garden to sit out of the weather, whatever the season.', 'label' => 'Garden room photo'],
            ['name' => 'Balcony', 'where' => 'First floor, off the Lion suite', 'description' => 'A large balcony over the garden and the entertaining patio, reached through the double doors in the Lion suite. Good for a first coffee of the day.', 'label' => 'Balcony photo'],
            ['name' => 'Gym', 'where' => 'The grounds', 'description' => 'Fully equipped in its own building in the grounds, with cardio, weights and racks. Over-16s only.', 'label' => 'Gym photo'],
            ['name' => 'Office', 'where' => 'The grounds', 'description' => 'A purpose-built, hard-wired office in the grounds, with a desk and fast broadband for remote working.', 'label' => 'Office photo'],
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    public static function defaultWalks(): array
    {
        return [
            [
                'name' => 'The Braunston lock flight and tunnel',
                'description' => 'From the marina follow the Grand Union towpath east past the six locks to the mouth of Braunston Tunnel. The Admiral Nelson at Lock 3 is the natural half-way stop. Return the same way, or cross at the tunnel and come back over the fields.',
                'distance' => 'About 4 miles',
                'time' => '1½–2 hours',
                'terrain' => 'Flat towpath, can be muddy',
                'stop' => 'Admiral Nelson',
            ],
            [
                'name' => 'Willoughby circular',
                'description' => 'Out along the Oxford Canal towpath towards Willoughby, then back across the fields and the old Roman road through the village. Quiet lanes and open country.',
                'distance' => 'About 5 miles',
                'time' => '2–2½ hours',
                'terrain' => 'Towpath and field paths',
                'stop' => 'Willoughby village',
            ],
            [
                'name' => 'Ashby St Ledgers and the Gunpowder Plot',
                'description' => 'Across the fields to Ashby St Ledgers, where the conspirators met in the manor gatehouse in 1605. The village has a thatched street and the Manor House.',
                'distance' => 'About 6 miles',
                'time' => '2½–3 hours',
                'terrain' => 'Field paths, some stiles',
                'stop' => 'Ashby St Ledgers',
            ],
        ];
    }

    /**
     * @return array<int, array{title: string, items: array<int, string>, flag?: bool}>
     */
    public static function defaultBookingRules(): array
    {
        return [
            [
                'title' => 'Who can book',
                'items' => [
                    'The lead guest must be 21 or over and must stay at the property for the whole booking.',
                    'The booking is for the whole house only. We do not let individual rooms.',
                    'Bookings made directly with us are 10% cheaper than the same dates on Airbnb, Booking.com or Vrbo.',
                    'Overnight occupancy is capped at 12 adults and 2 children. This is a fire and insurance limit and cannot be exceeded.',
                    'Additional guests are welcome during the day for an event or gathering. Please tell us the expected numbers when you book.',
                    'We ask for a full guest list, with names and ages of any children, before arrival.',
                ],
            ],
            [
                'title' => 'Length of stay',
                'items' => [
                    'Minimum stay of 2 nights.',
                    'Minimum of 3 nights over bank holiday weekends, and 3 nights over Christmas and New Year.',
                    'Check-in from 3:00pm. Check-out by 12:00 noon.',
                    'Earlier check-in or later check-out may be possible if the house is free either side. Please ask; it is never guaranteed.',
                ],
            ],
            [
                'title' => 'Paying',
                'items' => [
                    'A non-refundable booking fee of 30% of the accommodation cost is payable when you book. This confirms the dates.',
                    'The balance is due 28 days before arrival. For bookings made within 28 days of arrival, the full amount is payable at the time of booking.',
                    'Payment by bank transfer or card. We do not accept cash.',
                    'Prices include the cleaning fee, linen, towels and utilities.',
                ],
            ],
            [
                'title' => 'Security deposit',
                'items' => [
                    'A refundable security deposit of &pound;950 is payable 7 days before arrival, by bank transfer or a card pre-authorisation.',
                    'It is returned in full within 7 days of departure, provided the house is left as found, there is no damage beyond fair wear and tear, and no rules have been broken.',
                    'We will always send you photographs and a written explanation of any deduction before it is made.',
                    'The deposit is a contribution, not a cap. If damage exceeds &pound;950, the lead guest remains liable for the balance.',
                ],
            ],
            [
                'title' => 'Agreement and identification',
                'items' => [
                    'Direct bookings require a short written rental agreement, signed by the lead guest before arrival.',
                    'We ask the lead guest for one form of photo identification (passport or driving licence) and proof of home address dated within the last three months.',
                    'We use this only to confirm you are who you say you are. It is stored securely, is not shared with anyone, and is deleted within 30 days of your departure unless there is an open damage claim.',
                    'We may decline a booking, without giving a reason, if identification is not provided.',
                ],
            ],
            [
                'title' => 'Changes and cancellations',
                'items' => [
                    'Cancellations follow our refund policy, which mirrors Airbnb&rsquo;s Moderate policy.',
                    'Date changes are treated as a cancellation and a new booking, though we will always try to move you if we can re-let the dates.',
                    'We strongly recommend travel insurance that covers cancellation.',
                    '<a href="#refunds">Read the full refund policy</a>',
                ],
            ],
        ];
    }

    /**
     * @return array<int, array{title: string, items: array<int, string>, flag?: bool}>
     */
    public static function defaultHouseRules(): array
    {
        return [
            [
                'title' => 'Events and gatherings',
                'items' => [
                    'The house is built for entertaining and we are happy for you to hold an event or function here. Please tell us what you are planning when you book.',
                    'Additional guests may join you during the day. Overnight numbers are strictly capped at 12 adults and 2 children.',
                    'Amplified music, sound systems and DJs are fine within the quiet hours below.',
                    'For a larger event, please talk to us first about parking, numbers and anything you plan to bring in.',
                ],
            ],
            [
                'title' => 'No smoking or vaping',
                'flag' => true,
                'items' => [
                    'No smoking or vaping anywhere inside the house or the garden buildings.',
                    'Smoking is permitted outdoors on the patio only. Please use the ashtray provided.',
                    'Evidence of smoking indoors results in a deduction from the security deposit to cover specialist cleaning.',
                    'No candles, incense, fireworks, Chinese lanterns or open flames anywhere on the property.',
                ],
            ],
            [
                'title' => 'Gym',
                'items' => [
                    'A gym waiver is displayed on the gym door and left on the table with the keys. Please read it before anyone uses the equipment.',
                    'The lead guest accepts the waiver on behalf of the party as part of the booking, and is responsible for making sure everyone has read it.',
                    'Over-16s only. Children must not enter the gym at any time.',
                    'Use the equipment at your own risk, and never alone: always have someone else present.',
                    'Do not use the gym after drinking alcohol.',
                ],
            ],
            [
                'title' => 'Hot tub',
                'items' => [
                    'Available from 8:00am to 11:00pm, in line with the quiet hours. It is close to neighbouring homes, so please keep noise down.',
                    'No glass on or near the patio. Plastic drinkware is provided.',
                    'Please shower before use, and do not use it after drinking heavily.',
                    'Children must be supervised by an adult at all times. Not suitable for anyone who is pregnant or has a heart condition without medical advice.',
                    'Do not adjust the chemical dosing or the controls.',
                ],
            ],
            [
                'title' => 'Noise and neighbours',
                'flag' => true,
                'items' => [
                    'Music and noise are fine between 8:00am and 11:00pm.',
                    'From 11:00pm to 8:00am, please move indoors, close doors and windows, turn music off and keep the garden and patio clear.',
                    'This is a residential village and our neighbours are close by. The 11:00pm limit is the one rule we cannot be flexible about.',
                    'Please park in the gated parking only, which takes up to six cars, rather than on Old Road.',
                ],
            ],
            [
                'title' => 'The house and grounds',
                'items' => [
                    'The garage and its outbuildings are private working areas and are not part of the let. No access at any time.',
                    'Please use the Kadai barbecue only in the position provided, and never under cover or close to the building.',
                    'No pets.',
                    'Please report any damage or breakage to us during your stay rather than after it. Accidents happen and we would much rather know.',
                    'Please leave the house tidy, with rubbish and recycling in the bins outside and furniture back where you found it. You do not need to clean &mdash; that is what the cleaning fee is for.',
                ],
            ],
        ];
    }
}
