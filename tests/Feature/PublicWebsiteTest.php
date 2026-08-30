<?php

namespace Tests\Feature;

use App\Models\KnowledgeBaseArticle;
use App\Models\Property;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->assertSee('Book now')
            ->assertSee('data-chat-widget', false)
            ->assertSee('data-source="website"', false);
    }

    public function test_property_and_booking_pages_render(): void
    {
        $property = Property::factory()->create();
        Room::factory()->create(['property_id' => $property->id, 'name' => 'Garden Room']);

        $this->get(route('property'))->assertOk()->assertSee('Garden Room');
        $this->get(route('amenities'))->assertOk();
        $this->get(route('gallery'))->assertOk();
        $this->get(route('location'))->assertOk();
        $this->get(route('contact'))->assertOk();
        $this->get(route('privacy'))->assertOk();
        $this->get(route('booking.search'))->assertOk();
    }

    public function test_faq_page_shows_knowledge_base_articles(): void
    {
        KnowledgeBaseArticle::factory()->create([
            'title' => 'Is parking available?',
            'category' => 'parking',
            'status' => 'active',
        ]);

        $this->get(route('faq'))
            ->assertOk()
            ->assertSee('Is parking available?');
    }

    public function test_contact_form_sends_enquiry(): void
    {
        $this->post(route('contact.submit'), [
            'name' => 'Sam Guest',
            'email' => 'sam@example.com',
            'message' => 'Can we arrive early?',
        ])->assertRedirect();
    }
}
