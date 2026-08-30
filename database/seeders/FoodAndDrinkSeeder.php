<?php

namespace Database\Seeders;

use App\Models\FoodAndDrink;
use Illuminate\Database\Seeder;

class FoodAndDrinkSeeder extends Seeder
{
    public function run(): void
    {
        $establishments = [
            [
                'name' => 'Braunston Butcher',
                'slug' => 'braunston-butcher',
                'description' => 'A traditional village butcher offering high-quality local meats. Ask about our breakfast package promotion — fresh sausages, bacon, and eggs delivered to the house for the perfect countryside breakfast.',
                'category' => 'butcher',
                'address' => 'Braunston, Daventry, Northamptonshire',
                'phone' => '',
                'website' => '',
                'is_featured' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'The Old Boat',
                'slug' => 'the-old-boat',
                'description' => 'A charming waterside pub near Braunston Marina serving classic British pub food and refreshing drinks. A popular spot for boaters and walkers alike.',
                'category' => 'pub',
                'address' => 'Braunston, Daventry, Northamptonshire',
                'phone' => '',
                'website' => '',
                'is_featured' => false,
                'sort_order' => 2,
            ],
            [
                'name' => 'Gongoozlers Rest',
                'slug' => 'gongoozlers-rest',
                'description' => 'A cosy café and restaurant with a relaxed atmosphere, perfect for a leisurely lunch or afternoon tea. Known for its friendly service and locally sourced ingredients.',
                'category' => 'cafe',
                'address' => 'Braunston, Daventry, Northamptonshire',
                'phone' => '',
                'website' => '',
                'is_featured' => false,
                'sort_order' => 3,
            ],
            [
                'name' => 'Admiral Nelson',
                'slug' => 'admiral-nelson',
                'description' => 'A historic canal-side pub offering a warm welcome, real ales, and hearty meals. Ideal for a post-walk pint or a relaxed evening dinner.',
                'category' => 'pub',
                'address' => 'Braunston, Daventry, Northamptonshire',
                'phone' => '',
                'website' => '',
                'is_featured' => false,
                'sort_order' => 4,
            ],
            [
                'name' => 'The Old Plough',
                'slug' => 'the-old-plough',
                'description' => 'A traditional English pub serving home-cooked food and a fine selection of drinks. A local favourite with a welcoming atmosphere.',
                'category' => 'pub',
                'address' => 'Braunston, Daventry, Northamptonshire',
                'phone' => '',
                'website' => '',
                'is_featured' => false,
                'sort_order' => 5,
            ],
            [
                'name' => 'Braunston Fish & Chips',
                'slug' => 'braunston-fish-and-chips',
                'description' => 'Classic fish and chips made with fresh, locally sourced fish. A must-visit for a traditional British takeaway experience.',
                'category' => 'takeaway',
                'address' => 'Braunston, Daventry, Northamptonshire',
                'phone' => '',
                'website' => '',
                'is_featured' => false,
                'sort_order' => 6,
            ],
            [
                'name' => 'The Boat Shop & Café',
                'slug' => 'the-boat-shop-cafe',
                'description' => 'A unique combination of boat supplies and a charming café, perfect for a morning coffee or a light lunch while watching the canal boats go by.',
                'category' => 'cafe',
                'address' => 'Braunston Marina, Daventry, Northamptonshire',
                'phone' => '',
                'website' => '',
                'is_featured' => false,
                'sort_order' => 7,
            ],
        ];

        foreach ($establishments as $item) {
            FoodAndDrink::firstOrCreate(['slug' => $item['slug']], $item);
        }
    }
}
