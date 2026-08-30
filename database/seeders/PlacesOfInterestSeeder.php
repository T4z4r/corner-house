<?php

namespace Database\Seeders;

use App\Models\PlacesOfInterest;
use Illuminate\Database\Seeder;

class PlacesOfInterestSeeder extends Seeder
{
    public function run(): void
    {
        $places = [
            [
                'name' => 'Braunston Marina',
                'slug' => 'braunston-marina',
                'description' => 'A beautiful canal marina at the junction of the Oxford and Grand Union canals. Perfect for a peaceful stroll, boat watching, or starting a canal-side walk.',
                'category' => 'attraction',
                'distance' => '0.5 miles',
                'sort_order' => 1,
            ],
            [
                'name' => 'Stratford upon Avon',
                'slug' => 'stratford-upon-avon',
                'description' => 'The birthplace of William Shakespeare, this historic market town offers world-class theatre, beautiful riverside walks, and charming streets filled with independent shops and restaurants.',
                'category' => 'town',
                'distance' => '18 miles',
                'sort_order' => 2,
            ],
            [
                'name' => 'Rugby Railway Station',
                'slug' => 'rugby-railway-station',
                'description' => 'The nearest major railway station with direct services to London Euston, Birmingham, and other major cities. Convenient for guests travelling by train.',
                'category' => 'transport',
                'distance' => '10 miles',
                'sort_order' => 3,
            ],
            [
                'name' => 'Shakespeare Country',
                'slug' => 'shakespeare-country',
                'description' => 'Explore the wider Warwickshire countryside that inspired the Bard. Rolling hills, historic villages, and stunning National Trust properties await.',
                'category' => 'attraction',
                'distance' => '15-25 miles',
                'sort_order' => 4,
            ],
            [
                'name' => 'Narrowboat Escapes',
                'slug' => 'narrowboat-escapes',
                'description' => 'Hire a narrowboat for a day or week and explore the beautiful canal network of Northamptonshire and beyond. A quintessential British waterway experience.',
                'category' => 'activity',
                'distance' => '1 mile',
                'sort_order' => 5,
            ],
            [
                'name' => 'Serendipity Art Shop',
                'slug' => 'serendipity-art-shop',
                'description' => 'A delightful independent art gallery and shop featuring local artists and unique handcrafted pieces. Perfect for finding a special souvenir.',
                'category' => 'shop',
                'distance' => '2 miles',
                'sort_order' => 6,
            ],
            [
                'name' => 'Warwick Castle',
                'slug' => 'warwick-castle',
                'description' => 'A magnificent medieval castle with over 1,000 years of history. Explore the grand interiors, ramparts, and grounds, or enjoy live shows and experiences.',
                'category' => 'attraction',
                'distance' => '15 miles',
                'sort_order' => 7,
            ],
            [
                'name' => 'Kenilworth Castle',
                'slug' => 'kenilworth-castle',
                'description' => 'The ruins of one of the largest and most beautiful castles in England, famously associated with Queen Elizabeth I and Robert Dudley.',
                'category' => 'attraction',
                'distance' => '18 miles',
                'sort_order' => 8,
            ],
            [
                'name' => 'Silverstone Circuit',
                'slug' => 'silverstone-circuit',
                'description' => 'Home of British motorsport and the British Grand Prix. Visit the museum, take a track tour, or drive experience day.',
                'category' => 'attraction',
                'distance' => '12 miles',
                'sort_order' => 9,
            ],
            [
                'name' => 'Draycote Water Country Park',
                'slug' => 'draycote-water',
                'description' => 'A stunning reservoir with a 5-mile circular walking and cycling trail, sailing, fishing, and a visitor centre with café.',
                'category' => 'nature',
                'distance' => '14 miles',
                'sort_order' => 10,
            ],
            [
                'name' => 'Whilton Mill',
                'slug' => 'whilton-mill',
                'description' => 'A go-karting and outdoor activity centre offering thrilling karting experiences for all ages. Great for families and groups.',
                'category' => 'activity',
                'distance' => '5 miles',
                'sort_order' => 11,
            ],
        ];

        foreach ($places as $place) {
            PlacesOfInterest::firstOrCreate(['slug' => $place['slug']], $place);
        }
    }
}
