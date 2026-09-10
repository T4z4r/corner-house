<?php

namespace Tests\Feature;

use App\Models\KnowledgeBaseArticle;
use App\Models\Property;
use App\Models\Review;
use App\Models\Room;
use App\Models\Setting;
use Database\Seeders\AmenitySeeder;
use Database\Seeders\RoomSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PublicWebsiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_renders(): void
    {
        Property::factory()->create(['name' => 'Corner House']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Corner House')
            ->assertSee('Check availability')
            ->assertSee('Built for a full house')
            ->assertSee('Serengeti Spirits')
            ->assertSee('#rooms', false)
            ->assertSee('data-page="rooms"', false)
            ->assertSee('images/logo.png', false)
            ->assertSee('data-chat-widget', false)
            ->assertSee('Ask Corner House');
    }

    public function test_home_page_shows_only_approved_reviews(): void
    {
        Property::factory()->create(['name' => 'Corner House']);
        Review::factory()->approved()->create([
            'stars' => 5,
            'quote' => 'The hot tub after a long walk was perfect.',
            'cite' => 'Sophie, June 2026',
        ]);
        Review::factory()->hidden()->create([
            'quote' => 'A review we have not approved yet.',
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('The hot tub after a long walk was perfect.')
            ->assertSee('Sophie, June 2026')
            ->assertDontSee('A review we have not approved yet.');
    }

    public function test_home_page_shows_review_claim(): void
    {
        Property::factory()->create(['name' => 'Corner House']);
        Review::factory()->approved()->create(['stars' => 5]);
        Review::factory()->approved()->create(['stars' => 4]);
        Review::factory()->hidden()->create(['stars' => 1]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('All 5 star reviews from more than 30 Airbnb guests within the first year of listing', false);
    }

    public function test_uploaded_logo_overrides_bundled_default(): void
    {
        Property::factory()->create(['name' => 'Corner House']);

        Setting::query()->create([
            'group' => 'website',
            'key' => 'website_logo',
            'value' => 'website/custom.png',
            'type' => 'image',
            'label' => 'Logo',
            'cast' => 'string',
        ]);

        cache()->forget('settings.all');

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('storage/website/custom.png', false)
            ->assertDontSee('images/logo.png', false);
    }

    public function test_favicon_uses_bundled_svg_regardless_of_logo_upload(): void
    {
        Property::factory()->create(['name' => 'Corner House']);

        Setting::query()->create([
            'group' => 'website',
            'key' => 'website_logo',
            'value' => 'website/custom.png',
            'type' => 'image',
            'label' => 'Logo',
            'cast' => 'string',
        ]);

        cache()->forget('settings.all');

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('rel="icon" type="image/svg+xml" href="http://localhost:8000/images/logo.svg"', false);
    }

    public function test_favicon_uses_bundled_svg_even_when_favicon_setting_uploaded(): void
    {
        Property::factory()->create(['name' => 'Corner House']);

        Setting::query()->create([
            'group' => 'website',
            'key' => 'website_logo',
            'value' => 'website/custom.png',
            'type' => 'image',
            'label' => 'Logo',
            'cast' => 'string',
        ]);
        Setting::query()->create([
            'group' => 'website',
            'key' => 'website_favicon',
            'value' => 'website/favicon.png',
            'type' => 'image',
            'label' => 'Small icon',
            'cast' => 'string',
        ]);

        cache()->forget('settings.all');

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('rel="icon" type="image/svg+xml" href="http://localhost:8000/images/logo.svg"', false)
            ->assertDontSee('storage/website/favicon.png', false);
    }

    public function test_property_and_booking_pages_render(): void
    {
        $property = Property::factory()->create();
        Room::factory()->create(['property_id' => $property->id, 'name' => 'Garden Room']);

        $this->get(route('property'))->assertOk()->assertSee('Garden Room');
        $this->get(route('amenities'))->assertOk();
        $this->get(route('gallery'))->assertOk();
        $this->get(route('location'))->assertOk()->assertSee('Open in Google Maps');
        $this->get(route('contact'))->assertOk();
        $this->get(route('privacy'))->assertOk();
        $this->get(route('booking.search'))->assertOk();
    }

    public function test_area_guide_page_shows_weather_and_local_events(): void
    {
        $property = Property::factory()->create(['latitude' => 52.234, 'longitude' => -0.893]);

        KnowledgeBaseArticle::factory()->create([
            'title' => 'August supper club',
            'category' => 'local-event',
            'content' => 'Live music and seasonal tasting menus this weekend.',
            'starts_at' => '2026-08-29',
            'ends_at' => '2026-08-30',
            'status' => 'active',
            'show_on_website' => true,
        ]);

        KnowledgeBaseArticle::factory()->create([
            'title' => 'September craft fair',
            'category' => 'area-event',
            'content' => 'A craft fair with local makers and food stalls.',
            'starts_at' => '2026-09-12',
            'ends_at' => '2026-09-13',
            'status' => 'active',
            'show_on_website' => true,
        ]);

        Http::fake([
            'api.open-meteo.com/*' => Http::response([
                'daily' => [
                    'time' => [now()->toDateString(), now()->addDay()->toDateString()],
                    'temperature_2m_max' => [23.5, 21.0],
                    'temperature_2m_min' => [14.0, 12.5],
                    'precipitation_probability_max' => [15, 45],
                    'weathercode' => [1, 3],
                ],
            ], 200),
        ]);

        $this->get(route('location'))
            ->assertOk()
            ->assertSee('Open in Google Maps')
            ->assertDontSee('Weather forecast');

        $this->get(route('area-guide', ['period' => 'week', 'date' => '2026-08-30']))
            ->assertOk()
            ->assertSee('Weather forecast')
            ->assertSee('August supper club')
            ->assertDontSee('September craft fair')
            ->assertSee('This week');

        $this->get(route('area-guide', ['period' => 'month', 'date' => '2026-09-15']))
            ->assertOk()
            ->assertSee('September craft fair')
            ->assertDontSee('August supper club')
            ->assertSee('This month');
    }

    public function test_faq_page_shows_knowledge_base_articles(): void
    {
        KnowledgeBaseArticle::factory()->create([
            'title' => 'Is parking available?',
            'category' => 'parking',
            'status' => 'active',
            'show_on_website' => true,
        ]);

        KnowledgeBaseArticle::factory()->create([
            'title' => 'Internal staff note',
            'category' => 'faqs',
            'status' => 'active',
            'show_on_website' => false,
        ]);

        $this->get(route('faq'))
            ->assertOk()
            ->assertSee('Is parking available?')
            ->assertDontSee('Internal staff note');
    }

    public function test_contact_form_sends_enquiry(): void
    {
        $this->post(route('contact.submit'), [
            'name' => 'Sam Guest',
            'email' => 'sam@example.com',
            'message' => 'Can we arrive early?',
        ])->assertRedirect()->assertSessionHas('status');

        $this->assertDatabaseHas('enquiries', [
            'type' => 'contact',
            'name' => 'Sam Guest',
            'email' => 'sam@example.com',
            'message' => 'Can we arrive early?',
            'status' => 'new',
        ]);
    }

    public function test_home_page_showcase_section_has_hero_figcaptions(): void
    {
        Property::factory()->create(['name' => 'Corner House']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('The front', false)
            ->assertSee('The garden', false)
            ->assertSee('hero-front.jpg', false)
            ->assertSee('hero-garden.jpg', false);
    }

    public function test_home_page_spirits_section_has_reference_structure(): void
    {
        Property::factory()->create(['name' => 'Corner House']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Made here at Corner House', false)
            ->assertSee('serengeti-logo.png', false)
            ->assertSee('Stock the house', false)
            ->assertSee('Custom bottles for the occasion', false)
            ->assertSee('Drinks package', false)
            ->assertSee('shop.serengetispirits.com', false);
    }

    public function test_home_page_foundation_section_has_wright_foundation_content(): void
    {
        Property::factory()->create(['name' => 'Corner House']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('The Wright Foundation', false)
            ->assertSee('Sintanizer', false)
            ->assertSee('JKT Wildlife, Kibaha', false)
            ->assertSee('cdn.shopify.com', false);
    }

    public function test_home_page_terms_section_has_legal_clauses(): void
    {
        Property::factory()->create(['name' => 'Corner House']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Terms and conditions', false)
            ->assertSee('Parties and the agreement', false)
            ->assertSee('Security deposit', false);
    }

    public function test_home_page_refunds_section_has_airbnb_moderate_policy(): void
    {
        Property::factory()->create(['name' => 'Corner House']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Cancellation and refund policy', false)
            ->assertSee('Within 24 hours of booking', false)
            ->assertSee('More than 5 days', false);
    }

    public function test_home_page_booking_and_house_rules_use_reference_content(): void
    {
        Property::factory()->create(['name' => 'Corner House']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Who can book', false)
            ->assertSee('Length of stay', false)
            ->assertSee('Changes and cancellations', false)
            ->assertSee('Events and gatherings', false)
            ->assertSee('No smoking or vaping', false)
            ->assertSee('Noise and neighbours', false);
    }

    public function test_booking_enquiry_accepts_the_widget_payload(): void
    {
        $this->postJson(route('booking.enquiry'), [
            'checkIn' => '2026-10-02',
            'checkOut' => '2026-10-04',
            'nights' => 2,
            'name' => 'Sam Guest',
            'email' => 'sam@example.com',
            'phone' => '07700 900123',
            'guests' => '12',
            'message' => 'A birthday weekend.',
            'drinksPackage' => true,
            'acceptedTerms' => true,
        ])->assertOk()->assertJson(['status' => 'ok']);

        $this->assertDatabaseHas('enquiries', [
            'type' => 'booking',
            'name' => 'Sam Guest',
            'email' => 'sam@example.com',
            'phone' => '07700 900123',
            'guests' => '12',
            'check_in' => '2026-10-02',
            'check_out' => '2026-10-04',
            'nights' => 2,
            'message' => 'A birthday weekend.',
            'drinks_package' => true,
            'terms_accepted' => true,
            'status' => 'new',
        ]);
    }

    public function test_booking_availability_returns_blocked_ranges(): void
    {
        Setting::query()->create([
            'group' => 'website',
            'key' => 'website_blocked_dates',
            'value' => json_encode(['2026-10-02', '2026-10-15']),
            'type' => 'text',
            'label' => 'Blocked dates',
            'cast' => 'json',
        ]);

        cache()->forget('settings.all');

        $this->getJson(route('booking.availability'))
            ->assertOk()
            ->assertJson([
                ['start' => '2026-10-02', 'end' => '2026-10-03'],
                ['start' => '2026-10-15', 'end' => '2026-10-16'],
            ]);
    }

    public function test_website_config_feeds_stay_on_the_same_origin(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('window.__SITE__', false)
            ->assertSee('"availabilityUrl":"\\/booking\\/availability"', false)
            ->assertSee('"bookingEndpoint":"\\/booking\\/enquiry"', false);
    }

    public function test_home_page_amenities_match_template_list(): void
    {
        $property = Property::factory()->create(['name' => 'Corner House', 'slug' => 'corner-house', 'status' => 'active']);
        $this->seed(AmenitySeeder::class);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Orangery dining room', false)
            ->assertSee('First-floor balcony', false)
            ->assertSee('Five ensuite bedrooms', false)
            ->assertSee('Hot tub on the patio', false)
            ->assertSee('Cinema room in the cellar', false)
            ->assertSee('Games room', false)
            ->assertSee('Garden bar and Kadai BBQ', false)
            ->assertSee('Fully equipped gym', false)
            ->assertSee('Hard-wired office', false)
            ->assertSee('Landscaped garden', false)
            ->assertSee('Private gated parking for 6 cars', false)
            ->assertSee('Sky TV in every bedroom', false)
            ->assertSee('EV charger', false)
            ->assertDontSee('Garden room and first-floor balcony', false);
    }

    public function test_home_page_rooms_ordered_by_sort_order(): void
    {
        $property = Property::factory()->create(['name' => 'Corner House', 'slug' => 'corner-house', 'status' => 'active']);
        $this->seed(RoomSeeder::class);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Lion', false)
            ->assertSee('Elephant', false)
            ->assertSee('Buffalo', false)
            ->assertSee('Rhino', false)
            ->assertSee('Leopard', false)
            ->assertSee('Master suite', false)
            ->assertSee('King or twin', false);
    }

    public function test_home_rooms_page_shows_bundled_kitchen_and_bedroom_photos_as_defaults(): void
    {
        $property = Property::factory()->create(['name' => 'Corner House', 'slug' => 'corner-house', 'status' => 'active']);
        $this->seed(RoomSeeder::class);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('http://localhost:8000/images/kitchen.png', false)
            ->assertSee('http://localhost:8000/images/orangery.png', false)
            ->assertSee('http://localhost:8000/images/lounge.png', false)
            ->assertSee('http://localhost:8000/images/cinema.webp', false)
            ->assertSee('http://localhost:8000/images/games.png', false)
            ->assertSee('http://localhost:8000/images/gym.png', false)
            ->assertSee('http://localhost:8000/images/garden.png', false)
            ->assertSee('http://localhost:8000/images/hot-tub.png', false)
            ->assertSee('http://localhost:8000/images/balcony.png', false)
            ->assertSee('http://localhost:8000/images/office.png', false)
            ->assertSee('http://localhost:8000/images/bedroom-lion.png', false)
            ->assertSee('http://localhost:8000/images/bedroom-elephant.png', false)
            ->assertSee('http://localhost:8000/images/bedroom-buffalo.png', false)
            ->assertSee('http://localhost:8000/images/bedroom-rhino.png', false)
            ->assertSee('http://localhost:8000/images/bedroom-leopard.png', false)
            ->assertDontSee('The kitchen photo', false);
    }

    public function test_uploaded_room_image_and_custom_space_photo_override_bundled_defaults(): void
    {
        $property = Property::factory()->create(['name' => 'Corner House', 'slug' => 'corner-house', 'status' => 'active']);
        $this->seed(RoomSeeder::class);

        $lion = Room::where('slug', 'lion-suite')->firstOrFail();
        $lion->images()->create(['path' => 'rooms/lion-upload.png', 'alt' => 'Lion suite', 'sort_order' => 1, 'is_primary' => true]);

        Setting::query()->create([
            'group' => 'website',
            'key' => 'website_spaces_inside',
            'value' => json_encode([
                ['name' => 'The kitchen', 'where' => 'Ground floor', 'description' => 'Custom kitchen copy.', 'label' => 'Kitchen photo', 'feature' => '1', 'photo' => 'storage/website/kitchen-upload.png'],
            ]),
            'cast' => 'json',
        ]);
        cache()->forget('settings.all');

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('http://localhost:8000/storage/rooms/lion-upload.png', false)
            ->assertDontSee('http://localhost:8000/images/bedroom-lion.png', false)
            ->assertSee('http://localhost:8000/images/bedroom-elephant.png', false)
            ->assertSee('http://localhost:8000/storage/website/kitchen-upload.png', false)
            ->assertDontSee('http://localhost:8000/images/kitchen.png', false);
    }

    public function test_home_kitchen_photo_follows_custom_space_photo(): void
    {
        Property::factory()->create(['name' => 'Corner House', 'slug' => 'corner-house', 'status' => 'active']);

        Setting::query()->create([
            'group' => 'website',
            'key' => 'website_spaces_inside',
            'value' => json_encode([
                ['name' => 'Kitchen', 'where' => 'Ground floor', 'description' => 'The 25-foot centrepiece.', 'label' => 'Kitchen photo', 'feature' => '1', 'photo' => 'storage/website/kitchen-upload.png'],
            ]),
            'cast' => 'json',
        ]);
        cache()->forget('settings.all');

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('http://localhost:8000/storage/website/kitchen-upload.png', false)
            ->assertDontSee('<div class="photo tall">Kitchen photo', false);
    }

    public function test_rooms_page_spaces_fall_back_to_bundled_images_when_no_photo(): void
    {
        Property::factory()->create(['name' => 'Corner House', 'slug' => 'corner-house', 'status' => 'active']);

        Setting::query()->create([
            'group' => 'website',
            'key' => 'website_spaces_inside',
            'value' => json_encode([
                ['name' => 'Kitchen', 'where' => 'Ground floor', 'description' => 'The kitchen.', 'label' => 'The kitchen photo', 'feature' => '1'],
                ['name' => 'Orangery', 'where' => 'Ground floor', 'description' => 'The orangery.', 'label' => 'Orangery photo'],
                ['name' => 'Lounge', 'where' => 'Ground floor', 'description' => 'The lounge.', 'label' => 'Lounge photo'],
                ['name' => 'Cinema room', 'where' => 'The converted cellar', 'description' => 'The cinema.', 'label' => 'Cinema room photo'],
                ['name' => 'Games room', 'where' => 'Ground floor', 'description' => 'The games.', 'label' => 'Games room photo'],
            ]),
            'cast' => 'json',
        ]);
        Setting::query()->create([
            'group' => 'website',
            'key' => 'website_spaces_outside',
            'value' => json_encode([
                ['name' => 'Garden bar', 'where' => 'The garden', 'description' => 'The bar.', 'label' => 'Garden bar photo'],
                ['name' => 'Gym', 'where' => 'The grounds', 'description' => 'The gym.', 'label' => 'Gym photo'],
                ['name' => 'Hot tub', 'where' => 'On the patio', 'description' => 'The tub.', 'label' => 'Hot tub photo'],
                ['name' => 'Balcony', 'where' => 'Off the Lion suite', 'description' => 'The balcony.', 'label' => 'Balcony photo'],
                ['name' => 'Office', 'where' => 'The grounds', 'description' => 'The office.', 'label' => 'Office photo'],
            ]),
            'cast' => 'json',
        ]);
        cache()->forget('settings.all');

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('http://localhost:8000/images/kitchen.png', false)
            ->assertDontSee('The kitchen photo', false)
            ->assertSee('http://localhost:8000/images/orangery.png', false)
            ->assertDontSee('Orangery photo', false)
            ->assertSee('http://localhost:8000/images/lounge.png', false)
            ->assertDontSee('Lounge photo', false)
            ->assertSee('http://localhost:8000/images/cinema.webp', false)
            ->assertDontSee('Cinema room photo', false)
            ->assertSee('http://localhost:8000/images/games.png', false)
            ->assertDontSee('Games room photo', false)
            ->assertSee('http://localhost:8000/images/gym.png', false)
            ->assertDontSee('Gym photo', false)
            ->assertSee('http://localhost:8000/images/garden.png', false)
            ->assertDontSee('Garden bar photo', false)
            ->assertSee('http://localhost:8000/images/hot-tub.png', false)
            ->assertDontSee('Hot tub photo', false)
            ->assertSee('http://localhost:8000/images/balcony.png', false)
            ->assertDontSee('Balcony photo', false)
            ->assertSee('http://localhost:8000/images/office.png', false)
            ->assertDontSee('Office photo', false);
    }

    public function test_home_page_shows_serengeti_bottle_photo(): void
    {
        Property::factory()->create(['name' => 'Corner House']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('http://localhost:8000/images/serengeti-bottle.png', false)
            ->assertDontSee('Serengeti Spirits bottle photo', false);
    }

    public function test_home_page_template_copy_matches(): void
    {
        $property = Property::factory()->create(['name' => 'Corner House', 'slug' => 'corner-house', 'status' => 'active', 'description' => null]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('The Wright Foundation', false)
            ->assertSee('Made here at Corner House', false)
            ->assertSee('the orangery which houses the dining room', false);
    }
}
