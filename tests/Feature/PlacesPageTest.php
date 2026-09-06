<?php

namespace Tests\Feature;

use App\Models\Property;
use Database\Seeders\PlacesOfInterestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlacesPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_places_tabs_match_template(): void
    {
        Property::factory()->create(['name' => 'Corner House']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Places of interest', false)
            ->assertSee('Food &amp; drink', false)
            ->assertSee('Days out', false)
            ->assertSee('Walking routes', false)
            ->assertSee('data-tab="food"', false)
            ->assertSee('data-tab="days"', false)
            ->assertSee('data-tab="walks"', false);
    }

    public function test_home_page_food_and_drink_places_match_template(): void
    {
        Property::factory()->create(['name' => 'Corner House']);
        $this->seed(PlacesOfInterestSeeder::class);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee("Gongoozler's Rest Café Boat")
            ->assertSee('0.2 miles · 4 min walk', false)
            ->assertSee('Café on a canal boat', false)
            ->assertSee('Seasonal — check Facebook', false)
            ->assertSee('Find them on Facebook', false)
            ->assertDontSee('gongoozlersrest.wixsite.com', false)
            ->assertSee('The Admiral Nelson', false)
            ->assertSee('Bar 12:00–23:00 · Food 12:00–15:00 and 17:00–21:00', false)
            ->assertSee('Dark Lane, Braunston NN11 7HJ', false)
            ->assertSee('tel:01788891900', false)
            ->assertSee('The Village Café', false)
            ->assertSee('Mon–Fri 10:30–14:30, Sat 10:30–12:30, closed Sun', false)
            ->assertSee('Hours change seasonally, particularly on the canal.', false);
    }

    public function test_home_page_days_out_places_match_template(): void
    {
        Property::factory()->create(['name' => 'Corner House']);
        $this->seed(PlacesOfInterestSeeder::class);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Braunston Marina', false)
            ->assertSee('On the doorstep', false)
            ->assertSee('Canal heritage', false)
            ->assertSee('Historic working boats, the original canal buildings', false)
            ->assertSee('Althorp House', false)
            ->assertSee('16 miles · 25 min', false)
            ->assertSee('Althorp NN7 4HQ', false)
            ->assertSee('Worcester Porcelain Museum', false)
            ->assertSee('48 miles · 80 min', false)
            ->assertSee('Distances and drive times are approximate.', false);
    }

    public function test_home_page_walking_routes_match_template(): void
    {
        Property::factory()->create(['name' => 'Corner House']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('The Braunston lock flight and tunnel', false)
            ->assertSee('About 4 miles', false)
            ->assertSee('1½–2 hours', false)
            ->assertSee('Flat towpath, can be muddy', false)
            ->assertSee('Admiral Nelson', false)
            ->assertSee('Willoughby circular', false)
            ->assertSee('2–2½ hours', false)
            ->assertSee('Ashby St Ledgers and the Gunpowder Plot', false)
            ->assertSee('2½–3 hours', false)
            ->assertSee('Field paths, some stiles', false);
    }

    public function test_home_page_places_page_only_renders_reference_copy(): void
    {
        Property::factory()->create(['name' => 'Corner House']);
        $this->seed(PlacesOfInterestSeeder::class);

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('Where to eat and drink in the village', false)
            ->assertDontSee('Everything below is within a fifteen-minute walk', false)
            ->assertDontSee('Days out within about fifty miles', false);
    }
}
