<?php

namespace Database\Seeders;

use App\Models\Property;
use App\Models\Room;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        $property = Property::firstOrCreate(
            ['slug' => 'corner-house'],
            ['name' => 'Corner House', 'slug' => 'corner-house', 'status' => 'active']
        );

        $rooms = [
            [
                'name' => 'Lion',
                'slug' => 'lion-suite',
                'description' => 'The master suite, at the front of the first floor with double doors onto the balcony over the garden. The largest bedroom in the house, with plenty of space for a cot or a day bed.',
                'type' => 'Master suite',
                'capacity' => 2,
                'sleeps' => 2,
                'bedrooms' => 1,
                'bathrooms' => 1,
                'is_private' => true,
                'status' => 'active',
                'base_rate' => 950.00,
                'min_stay' => 2,
                'max_stay' => 30,
                'features' => ['King bed', 'Ensuite', 'Balcony access', 'Garden view', 'Sky TV'],
                'sort_order' => 1,
            ],
            [
                'name' => 'Elephant',
                'slug' => 'elephant-room',
                'description' => 'A bright room at the back of the house overlooking the garden, with two windows. Converts from a king to twin singles and has its own ensuite.',
                'type' => 'Ensuite bedroom',
                'capacity' => 2,
                'sleeps' => 2,
                'bedrooms' => 1,
                'bathrooms' => 1,
                'is_private' => true,
                'status' => 'active',
                'base_rate' => 950.00,
                'min_stay' => 2,
                'max_stay' => 30,
                'features' => ['King or twin', 'Ensuite', 'Garden view', 'Sky TV'],
                'sort_order' => 2,
            ],
            [
                'name' => 'Leopard',
                'slug' => 'leopard-room',
                'description' => 'A comfortable first-floor room at the front of the house. Converts from a king to twin singles and has its own ensuite.',
                'type' => 'Ensuite bedroom',
                'capacity' => 2,
                'sleeps' => 2,
                'bedrooms' => 1,
                'bathrooms' => 1,
                'is_private' => true,
                'status' => 'active',
                'base_rate' => 950.00,
                'min_stay' => 2,
                'max_stay' => 30,
                'features' => ['King or twin', 'Ensuite', 'Sky TV'],
                'sort_order' => 3,
            ],
            [
                'name' => 'Rhino',
                'slug' => 'rhino-room',
                'description' => 'A ground-floor bedroom with level access throughout, making it the most accessible of the five. King bed and ensuite.',
                'type' => 'Ground floor ensuite',
                'capacity' => 2,
                'sleeps' => 2,
                'bedrooms' => 1,
                'bathrooms' => 1,
                'is_private' => true,
                'status' => 'active',
                'base_rate' => 950.00,
                'min_stay' => 2,
                'max_stay' => 30,
                'features' => ['King bed', 'Ensuite', 'Ground floor, level access', 'Sky TV'],
                'sort_order' => 4,
            ],
            [
                'name' => 'Buffalo',
                'slug' => 'buffalo-room',
                'description' => 'A cosy attic room at the top of the house with a king bed and ensuite under the eaves. Quiet and tucked away, ideal for a good night’s sleep.',
                'type' => 'Attic ensuite',
                'capacity' => 2,
                'sleeps' => 2,
                'bedrooms' => 1,
                'bathrooms' => 1,
                'is_private' => true,
                'status' => 'active',
                'base_rate' => 950.00,
                'min_stay' => 2,
                'max_stay' => 30,
                'features' => ['King bed', 'Ensuite', 'Under the eaves', 'Sky TV'],
                'sort_order' => 5,
            ],
        ];

        foreach ($rooms as $room) {
            Room::firstOrCreate(
                ['slug' => $room['slug']],
                array_merge($room, ['property_id' => $property->id]),
            );
        }
    }
}
