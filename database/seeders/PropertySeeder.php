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
                'description' => 'The house is arranged around a 25-foot kitchen, with the orangery which houses the dining room that seats ten at a handmade farmhouse table. Downstairs there is a games room and a cinema room in the converted cellar; outside, a garden bar, a Kadai fire-pit barbecue, a fully equipped gym and a hard-wired office in the grounds.',
                'short_description' => 'Corner House is a 175-year-old period home a few footsteps from Braunston Marina — five ensuite bedrooms, a 25ft kitchen built for entertaining, a games room and plenty of outside space, for family and friends to socialise and enjoy.',
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
                'is_primary' => true,
                'currency' => 'GBP',
                'smoking_allowed' => false,
                'children_allowed' => true,
                'parties_allowed' => false,
                'pets_allowed' => 'no',
                'check_in_from' => '15:00',
                'check_in_until' => '18:00',
                'check_out_from' => '08:00',
                'check_out_until' => '12:00',
                'custom_rules' => "• Check-in from 3pm, check-out by 12pm\n• 2 nights minimum stay (3 nights on bank holiday weekends)\n• 48 hours advance booking notice required\n• No same-day bookings\n• No smoking anywhere inside the property\n• Quiet hours after 10pm\n• No parties or events without prior arrangement\n• Maximum 12 adults, 2 infants (under 6), 2 cots\n• No pets allowed\n• Damage deposit required for direct bookings\n• Please treat the house and its contents with respect\n• Damage deposit may be charged for any breakages or damage",
            ],
        );
    }
}
