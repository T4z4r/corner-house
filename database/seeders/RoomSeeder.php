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
                'name' => 'Lion Suite',
                'slug' => 'lion-suite',
                'description' => 'The Lion Suite is our most spacious room, featuring regal lion-themed décor and imagery. A luxurious king-size bed, en-suite bathroom, and stunning views make this the crown jewel of Corner House. Perfect for those who want the ultimate retreat.',
                'type' => 'suite',
                'capacity' => 3,
                'sleeps' => 2,
                'bedrooms' => 1,
                'bathrooms' => 1,
                'is_private' => true,
                'status' => 'active',
                'base_rate' => 195.00,
                'min_stay' => 2,
                'max_stay' => 30,
            ],
            [
                'name' => 'Elephant Room',
                'slug' => 'elephant-room',
                'description' => 'The Elephant Room offers a warm and welcoming atmosphere with elephant-themed artwork and furnishings. Featuring a comfortable king-size bed and shared bathroom, this room combines character with comfort for a memorable stay.',
                'type' => 'bedroom',
                'capacity' => 2,
                'sleeps' => 2,
                'bedrooms' => 1,
                'bathrooms' => 0,
                'is_private' => true,
                'status' => 'active',
                'base_rate' => 145.00,
                'min_stay' => 2,
                'max_stay' => 30,
            ],
            [
                'name' => 'Buffalo Room',
                'slug' => 'buffalo-room',
                'description' => 'The Buffalo Room brings the spirit of the African plains indoors with bold buffalo imagery and earthy tones. A cozy double bed and thoughtful touches make this a favourite among guests seeking a unique countryside experience.',
                'type' => 'bedroom',
                'capacity' => 2,
                'sleeps' => 2,
                'bedrooms' => 1,
                'bathrooms' => 0,
                'is_private' => true,
                'status' => 'active',
                'base_rate' => 135.00,
                'min_stay' => 2,
                'max_stay' => 30,
            ],
            [
                'name' => 'Rhino Room',
                'slug' => 'rhino-room',
                'description' => 'The Rhino Room is a stylish and comfortable bedroom with distinctive rhino-themed design elements. With a plush double bed and easy access to shared facilities, it is an excellent choice for couples or solo travellers.',
                'type' => 'bedroom',
                'capacity' => 2,
                'sleeps' => 2,
                'bedrooms' => 1,
                'bathrooms' => 0,
                'is_private' => true,
                'status' => 'active',
                'base_rate' => 135.00,
                'min_stay' => 2,
                'max_stay' => 30,
            ],
            [
                'name' => 'Leopard Room',
                'slug' => 'leopard-room',
                'description' => 'The Leopard Room features sleek, spotted accents and leopard imagery that capture the grace and agility of this magnificent creature. A comfortable double bed and tasteful décor create the perfect retreat for a relaxing countryside stay.',
                'type' => 'bedroom',
                'capacity' => 2,
                'sleeps' => 2,
                'bedrooms' => 1,
                'bathrooms' => 0,
                'is_private' => true,
                'status' => 'active',
                'base_rate' => 135.00,
                'min_stay' => 2,
                'max_stay' => 30,
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
