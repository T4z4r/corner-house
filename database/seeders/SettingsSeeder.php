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
            ['group' => 'website', 'key' => 'website_reviews', 'value' => json_encode(SettingsSeeder::defaultReviews()), 'label' => 'Home — guest reviews', 'cast' => 'json'],
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
     * @return array<int, array{title: string, items: array<int, string>}>
     */
    public static function defaultBookingRules(): array
    {
        return [
            [
                'title' => 'Pricing and payment',
                'items' => [
                    'The whole house is let to one party at a time, for a minimum of 2 nights.',
                    'The nightly rate is &pound;950 low season, &pound;1,100 shoulder and &pound;1,350 high season, plus a &pound;250 cleaning fee and a refundable security deposit.',
                    'A non-refundable deposit of 25% secures the dates, with the balance due 8 weeks before arrival.',
                ],
            ],
            [
                'title' => 'Dates and changes',
                'items' => [
                    'Check-in from 5:00pm, check-out by 10:00am on the day of departure.',
                    'Changes to a confirmed booking are subject to availability and may carry a fee.',
                    'We hold dates for 7 days while an enquiry or deposit is outstanding.',
                ],
            ],
            [
                'title' => 'Security deposit',
                'items' => [
                    'A refundable security deposit is collected with the balance and returned within 7 days of departure, less any deductions for damage or breakages.',
                    'The hot tub, gym and garden bar are included in the rate; extra charges apply for event furniture hire and some add-ons.',
                ],
            ],
            [
                'title' => 'Cancellation',
                'items' => [
                    'See our <a href="#refunds">refund policy</a> for the amounts payable if you cancel.',
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
                'title' => 'To keep you and the house safe',
                'items' => [
                    'No unaccompanied children. The hot tub, gym, canal and Marina are not supervised, and the garden pond is deep.',
                    'The hot tub is used at your own risk. No alcohol or glass on the poolside. Over-12s only for the hot tub; over-16s only for the gym.',
                ],
            ],
            [
                'title' => 'To keep neighbours happy',
                'items' => [
                    'Be courteous to our neighbours, especially during garden social evenings and late arrivals. Noise carries in the village.',
                ],
            ],
            [
                'title' => 'To keep the house in good order',
                'items' => [
                    'No smoking or vaping anywhere in the house, outbuilding or on the grounds. A &pound;200 cleaning charge applies for smoking indoors or the use of a vape.',
                    'Up to 12 adults plus 2 children, in five bedrooms. Different occupancy requires prior written agreement.',
                    'Only use a barbecue in the designated Kadai and garden bar, well clear of the house and outbuildings. Never leave a hot barbecue unattended.',
                    'The cinema room and games room are in the cellar; take care on the cellar stairs.',
                ],
            ],
            [
                'title' => 'And finally',
                'flag' => true,
                'items' => [
                    'If we think you are breaking the house rules, we will talk to you, of course. But persistent or serious breaches of the rules are not tolerated and may result in the termination of your booking without refund.',
                ],
            ],
        ];
    }

    /**
     * @return array<int, array{stars: int, quote: string, cite: string}>
     */
    public static function defaultReviews(): array
    {
        return [
            ['stars' => 5, 'quote' => 'Corner House was the perfect base for our family gathering. The kitchen really is the heart of it — we all ended up around that table, and the cinema room was a huge hit with the kids.', 'cite' => 'The Hamiltons, July 2026'],
            ['stars' => 5, 'quote' => 'Beautiful house, beautifully kept. The hot tub after a long walk to Ashby St Ledgers was exactly what we needed.', 'cite' => 'Sophie and Tom, June 2026'],
            ['stars' => 5, 'quote' => 'The garden and entertaining patio are even better than the photos. We cooked on the fire-pit both nights and barely wanted to leave.', 'cite' => 'Mark, May 2026'],
            ['stars' => 4, 'quote' => 'Great location for the Marina and a proper kitchen for cooking for twelve. The office in the grounds was a lifesaver for a mid-week call.', 'cite' => 'Priya, April 2026'],
            ['stars' => 5, 'quote' => 'We organised a birthday weekend here and the whole party was looked after brilliantly. The rooms are huge and each one having its own bathroom is a treat.', 'cite' => 'James, March 2026'],
            ['stars' => 5, 'quote' => 'Faultless. The gym is better equipped than most hotels, and the welcome on arrival was warm and easy. We will be back.', 'cite' => 'Rachel, February 2026'],
            ['stars' => 5, 'quote' => 'A proper country house for a full house. Everyone who stayed wants to come again — the balcony off the Lion suite at sunrise is magic.', 'cite' => 'The O\'Briens, January 2026'],
            ['stars' => 4, 'quote' => 'Lovely stay at the Heart of the Waterways. The Gongoozlers Rest for breakfast and a walk along the towpath were highlights.', 'cite' => 'Daniel, December 2025'],
            ['stars' => 5, 'quote' => 'We stayed over New Year and the games room, cinema room and garden bar kept a big mixed group entertained for days.', 'cite' => 'Anna, January 2026'],
            ['stars' => 5, 'quote' => 'Booked the Serengeti Spirits tasting on site and it made the weekend. Great house, great hosts, great gin.', 'cite' => 'Ben, November 2025'],
            ['stars' => 5, 'quote' => 'Everything was exactly as described and the beds were wonderfully comfortable. A perfect retreat from the city.', 'cite' => 'Charlotte, October 2025'],
        ];
    }
}
