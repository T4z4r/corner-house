<?php

namespace Tests\Feature;

use App\Models\KnowledgeBaseArticle;
use App\Models\Property;
use App\Models\Room;
use App\Models\Setting;
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
            ->assertSee('data-page="rooms"', false);
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
        ])->assertRedirect();
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
}
