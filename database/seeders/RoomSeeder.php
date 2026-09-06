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
                'description' => 'The Lion suite comes with a roar and is the pick of the five bedrooms. A very spacious room with plenty of space to roam like the king of the jungle, with tasteful lion décor and tapestry. Open the double doors in the far corner onto a large balcony overlooking the garden and the entertaining patio — perfect for a morning or evening cuppa, or a cocktail. The real surprise lies behind the wardrobe doors, where you will find a secret ensuite hidden away.',
                'type' => 'Master suite · Second floor',
                'capacity' => 2,
                'sleeps' => 2,
                'bedrooms' => 1,
                'bathrooms' => 1,
                'is_private' => true,
                'status' => 'active',
                'base_rate' => 950.00,
                'min_stay' => 2,
                'max_stay' => 30,
                'features' => ['King-size Hypnos bed, bedside tables and touch lamps', 'Large TV with its own Sky puck', 'Ensuite with bath and shower', 'Custom-built wooden wardrobes with robes and hangers', 'Doors onto the large balcony', 'Comfy sofa and coffee table'],
                'sort_order' => 1,
            ],
            [
                'name' => 'Elephant',
                'slug' => 'elephant-room',
                'description' => 'Gentle and grand, the large Elephant room is a calming retreat in grey and pink. It sits on the second floor next to the Lion, with plenty of wardrobe space, a TV and a separate projector screen (usually kept in the wardrobe). Keep the king-size bed or convert the room into two singles. A large pink window box gives you somewhere to sit, relax and reflect.',
                'type' => 'King · Second floor',
                'capacity' => 2,
                'sleeps' => 2,
                'bedrooms' => 1,
                'bathrooms' => 1,
                'is_private' => true,
                'status' => 'active',
                'base_rate' => 950.00,
                'min_stay' => 2,
                'max_stay' => 30,
                'features' => ['King-size zip-and-link bed, splits into two singles', 'Bedside tables and lamps', 'Large TV with its own Sky puck', 'Elephant-sized ensuite with shower', 'Custom-built wardrobes with hangers'],
                'sort_order' => 2,
            ],
            [
                'name' => 'Buffalo',
                'slug' => 'buffalo-room',
                'description' => 'Nestled around the corner on the second floor, it is easy to miss the room dedicated to this graceful beast. It is far more welcoming than the untamed and rugged buffalo itself. Tastefully designed, with a large comfy bed and an ensuite, it is a room for lying back and thinking about a future safari.',
                'type' => 'King · Second floor',
                'capacity' => 2,
                'sleeps' => 2,
                'bedrooms' => 1,
                'bathrooms' => 1,
                'is_private' => true,
                'status' => 'active',
                'base_rate' => 950.00,
                'min_stay' => 2,
                'max_stay' => 30,
                'features' => ['King-size Hypnos bed with upper shelf and lamps', 'Large TV with its own Sky puck', 'Ensuite with shower', 'Wardrobe with hangers'],
                'sort_order' => 3,
            ],
            [
                'name' => 'Rhino',
                'slug' => 'rhino-room',
                'description' => 'Found on the third floor and styled in soft sage green, a nod to the gentle strength of the African rhino. A sizeable double with an original dark oak beam, close to two hundred years old, running across the ceiling. The ensuite has both a bath and a shower, a private place to unwind after a day in the countryside. Just outside the room is a further space to relax, with seating, a kettle tray and a mini fridge.',
                'type' => 'Double · Third floor',
                'capacity' => 2,
                'sleeps' => 2,
                'bedrooms' => 1,
                'bathrooms' => 1,
                'is_private' => true,
                'status' => 'active',
                'base_rate' => 950.00,
                'min_stay' => 2,
                'max_stay' => 30,
                'features' => ['Double Hypnos bed, bedside tables and lamps', 'Large TV with its own Sky puck', 'Ensuite with bath and shower', 'Custom-built wardrobe with hangers'],
                'sort_order' => 4,
            ],
            [
                'name' => 'Leopard',
                'slug' => 'leopard-room',
                'description' => 'Perched on the top floor opposite the Rhino, a sizeable double with the leopard’s versatility: keep the king-size bed or convert the room into two singles. A quirky cottage feel, with another original dark oak beam close to two hundred years old across the ceiling, and a newly built ensuite with a rain shower. Just outside the room is a further space to relax, with seating, a kettle tray and a mini fridge.',
                'type' => 'King or twin · Third floor',
                'capacity' => 2,
                'sleeps' => 2,
                'bedrooms' => 1,
                'bathrooms' => 1,
                'is_private' => true,
                'status' => 'active',
                'base_rate' => 950.00,
                'min_stay' => 2,
                'max_stay' => 30,
                'features' => ['King-size zip-and-link bed, splits into two singles', 'Bedside tables and lamps', 'Large TV with its own Sky puck', 'Ensuite with rain shower', 'Custom-built wardrobe with hangers'],
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
