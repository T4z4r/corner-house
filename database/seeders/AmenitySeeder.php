<?php

namespace Database\Seeders;

use App\Models\Amenity;
use Illuminate\Database\Seeder;

class AmenitySeeder extends Seeder
{
    public function run(): void
    {
        $amenities = [
            ['name' => 'Free Wi-Fi', 'icon' => 'bi-wifi', 'category' => 'Essentials', 'description' => 'Complimentary high-speed Wi-Fi throughout the property.', 'is_active' => true],
            ['name' => 'Heating', 'icon' => 'bi-thermometer-half', 'category' => 'Essentials', 'description' => 'Central heating in all rooms for a comfortable stay.', 'is_active' => true],
            ['name' => 'Towels & Linens', 'icon' => 'bi-box', 'category' => 'Essentials', 'description' => 'Fresh towels and bed linens provided for every guest.', 'is_active' => true],
            ['name' => 'Hair Dryer', 'icon' => 'bi-wind', 'category' => 'Essentials', 'description' => 'A hair dryer is available in the bathroom.', 'is_active' => true],
            ['name' => 'Iron & Board', 'icon' => 'bi-laptop', 'category' => 'Essentials', 'description' => 'An iron and ironing board are available on request.', 'is_active' => true],

            ['name' => 'Coffee Machine', 'icon' => 'bi-cup-hot', 'category' => 'Kitchen', 'description' => 'A pod-style coffee machine with a selection of capsules.', 'is_active' => true],
            ['name' => 'Full Kitchen', 'icon' => 'bi-fire', 'category' => 'Kitchen', 'description' => 'A fully equipped kitchen with oven, hob, fridge, and utensils.', 'is_active' => true],
            ['name' => 'Dishwasher', 'icon' => 'bi-droplet', 'category' => 'Kitchen', 'description' => 'A dishwasher for easy cleanup after your stay.', 'is_active' => true],
            ['name' => 'Washing Machine', 'icon' => 'bi-droplets', 'category' => 'Kitchen', 'description' => 'A washing machine and drying area for longer stays.', 'is_active' => true],

            ['name' => 'Smart TV', 'icon' => 'bi-tv', 'category' => 'Entertainment', 'description' => 'A Smart TV with streaming services in the living area.', 'is_active' => true],
            ['name' => 'Bluetooth Speaker', 'icon' => 'bi-speaker', 'category' => 'Entertainment', 'description' => 'A portable Bluetooth speaker for your music.', 'is_active' => true],
            ['name' => 'Board Games', 'icon' => 'bi-grid-3x3-gap', 'category' => 'Entertainment', 'description' => 'A selection of board games and card games for rainy days.', 'is_active' => true],

            ['name' => 'Free Parking', 'icon' => 'bi-car-front', 'category' => 'Outdoor', 'description' => 'Free off-street parking for one vehicle per room.', 'is_active' => true],
            ['name' => 'Garden', 'icon' => 'bi-flower1', 'category' => 'Outdoor', 'description' => 'Access to a shared garden with seating area.', 'is_active' => true],
            ['name' => 'Patio', 'icon' => 'bi-sun', 'category' => 'Outdoor', 'description' => 'A private patio area with outdoor furniture.', 'is_active' => true],

            ['name' => 'Keyless Entry', 'icon' => 'bi-key', 'category' => 'Convenience', 'description' => 'A keyless smart lock for flexible check-in and check-out.', 'is_active' => true],
            ['name' => 'Self Check-in', 'icon' => 'bi-phone', 'category' => 'Convenience', 'description' => 'Check in at your own pace with our self check-in process.', 'is_active' => true],
            ['name' => 'Luggage Storage', 'icon' => 'bi-briefcase', 'category' => 'Convenience', 'description' => 'Store your luggage before check-in or after check-out.', 'is_active' => true],
            ['name' => 'Workspace', 'icon' => 'bi-laptop', 'category' => 'Convenience', 'description' => 'A dedicated workspace with desk and chair in select rooms.', 'is_active' => true],

            // Headline features shown on the home page and in the house sections.
            ['name' => 'Five ensuite bedrooms', 'icon' => 'bi-door-open', 'category' => 'The house', 'description' => 'Five large bedrooms, every one with its own ensuite.', 'is_active' => true],
            ['name' => 'Hot tub on the patio', 'icon' => 'bi-water', 'category' => 'Outside', 'description' => 'A hot tub on the entertaining patio, available from 8:00am to 11:00pm.', 'is_active' => true],
            ['name' => 'Cinema room in the cellar', 'icon' => 'bi-film', 'category' => 'The house', 'description' => 'A converted cellar cinema room with a projector and seating for the whole party.', 'is_active' => true],
            ['name' => 'Games room', 'icon' => 'bi-controller', 'category' => 'The house', 'description' => 'Pool table, darts, board games and a console.', 'is_active' => true],
            ['name' => 'Garden bar and Kadai BBQ', 'icon' => 'bi-cup-straw', 'category' => 'Outside', 'description' => 'A garden bar and Kadai fire-pit barbecue beside the entertaining patio.', 'is_active' => true],
            ['name' => 'Fully equipped gym', 'icon' => 'bi-dumbbell', 'category' => 'Outside', 'description' => 'A private gym in the grounds with cardio, weights and racks. Over-16s only.', 'is_active' => true],
            ['name' => 'Hard-wired office', 'icon' => 'bi-briefcase', 'category' => 'Outside', 'description' => 'A dedicated, hard-wired office in the grounds with fast broadband.', 'is_active' => true],
            ['name' => 'Garden room and first-floor balcony', 'icon' => 'bi-tree', 'category' => 'Outside', 'description' => 'A garden room and a balcony off the Lion suite over the grounds.', 'is_active' => true],
            ['name' => 'Landscaped garden', 'icon' => 'bi-flower1', 'category' => 'Outside', 'description' => 'A tranquil landscaped garden laid out for a full house.', 'is_active' => true],
            ['name' => 'Private gated parking for 6 cars', 'icon' => 'bi-car-front', 'category' => 'Outside', 'description' => 'Private gated parking for up to six cars.', 'is_active' => true],
            ['name' => 'Sky TV in every bedroom', 'icon' => 'bi-tv', 'category' => 'The house', 'description' => 'A large TV with its own Sky puck in every bedroom.', 'is_active' => true],
        ];

        $designAmenities = [
            'Five ensuite bedrooms',
            'Hot tub on the patio',
            'Cinema room in the cellar',
            'Games room',
            'Garden bar and Kadai BBQ',
            'Fully equipped gym',
            'Hard-wired office',
            'Garden room and first-floor balcony',
            'Landscaped garden',
            'Private gated parking for 6 cars',
            'Sky TV in every bedroom',
        ];

        foreach ($amenities as $amenity) {
            Amenity::firstOrCreate(['name' => $amenity['name']], $amenity);
        }

        $property = \App\Models\Property::where('slug', 'corner-house')->first();

        if ($property) {
            $property->amenities()->sync(
                Amenity::whereIn('name', $designAmenities)->pluck('id')->all(),
            );
        }
    }
}
