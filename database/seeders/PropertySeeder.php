<?php

namespace Database\Seeders;

use App\Models\Property;
use Illuminate\Database\Seeder;

class PropertySeeder extends Seeder
{
    public function run(): void
    {
        Property::firstOrCreate(
            ['slug' => 'corner-house'],
            [
                'name' => 'Corner House',
                'slug' => 'corner-house',
                'description' => 'Corner House is a beautifully restored luxury countryside retreat nestled in the heart of Braunston, Northamptonshire. With five individually themed bedrooms inspired by African wildlife, the house offers a unique blend of character, comfort, and modern amenities. Whether you are planning a family gathering, a countryside escape, or a celebration with friends, Corner House provides the perfect setting with flexible booking options — individual rooms or the entire house.',
                'short_description' => 'A luxury countryside retreat in Braunston, Northamptonshire with five themed bedrooms and flexible booking options.',
                'address_line_1' => 'Corner House',
                'address_line_2' => 'Main Street',
                'city' => 'Braunston',
                'postcode' => 'NN7 7ND',
                'country' => 'GB',
                'latitude' => 52.2833,
                'longitude' => -1.2167,
                'capacity' => 12,
                'bedrooms' => 5,
                'bathrooms' => 4,
                'status' => 'active',
                'currency' => 'GBP',
                'smoking_allowed' => false,
                'children_allowed' => true,
                'parties_allowed' => false,
                'pets_allowed' => 'no',
                'check_in_from' => '15:00',
                'check_in_until' => '18:00',
                'check_out_from' => '08:00',
                'check_out_until' => '10:00',
                'custom_rules' => "• Check-in from 3pm, check-out by 10am\n• No smoking anywhere inside the property\n• Quiet hours after 10pm\n• No parties or events without prior arrangement\n• Maximum occupancy as per booking confirmation\n• Please treat the house and its contents with respect\n• Damage deposit may be charged for any breakages or damage",
            ],
        );
    }
}
