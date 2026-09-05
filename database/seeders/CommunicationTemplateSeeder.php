<?php

namespace Database\Seeders;

use App\Models\CommunicationTemplate;
use Illuminate\Database\Seeder;

class CommunicationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $bookingConfirmationBody = <<<'BODY'
Hi {{guest_name}},

Your stay at Corner House is nearly here and hopefully you are looking forward to your trip :)

Everything you need is below. Do have a read before you set off.

FINDING THE HOUSE
As you turn into Old Road, Corner House is the first house on the right - covered in ivy with a pink door, directly opposite the red phone box. There are no house numbers on the road, so look for the name.

PARKING
Don't stop at the house. Carry on about 50 yards and take the right turning through the open wooden gate (signed Corner House), then turn right again immediately after. The parking area holds around six cars - there's maybe a motorhome tucked in the corner.

GETTING IN
From the parking area, walk through the arch into the back garden. The back door into the kitchen is open and the keys are there. The front door and bedroom keys are already in their doors.

WI-FI
Network: Corner House
Password: Hakunamatata

TV
There are six Sky pucks - one in the lounge and one in each bedroom. Feel free to move one to the cellar cinema room or the outdoor TV if you'd like to use it there.

GARDEN BAR
All the remotes and accessories for the bar lighting and speakers are kept in the bar, along with the outside TV and electronic balcony cover. The Bluetooth code for the outside speakers is 1234. You'll also find a couple of garden games on the floor in here.

HOT TUB AND FIRE PIT
The hot tub is on and ready for use at 40 degrees. You're welcome to adjust the temperature, and there are spare chemicals on the rack. The Kadai BBQ and outdoor seating are opposite, and the fire pit and additional seating are up the steps by the lawn.

GAMES ROOM
The table is set up for pool but flips over for air hockey (this needs plugging into the socket). There's also a table tennis table and a dartboard on the wall. Board games are in the storage cupboard.

CINEMA ROOM (CELLAR)
This is ready to go. Please check the audio cable is pushed in at the back, and you may need to tilt or adjust the projector slightly to frame the picture. You can bring a Sky puck down or connect a laptop by HDMI. The white Majority remote operates the soundbar.

GYM
The gym is used entirely at your own (and your guests') risk. The key is on the kitchen counter on top of a liability waiver - please read this before use. There's a Google speaker in there: say "Hey Google" and ask it to play YouTube Music or whatever you would like.

ON DEPARTURE
Please lock the back kitchen door and leave the key in the office outbuilding.

A FEW EXTRAS
Please treat the house as your own and you can use anything in the property and outbuildings (except the Garage, which remains locked). There's some property information on the kitchen table, along with a complimentary bottle of Giraffe gin, which we produce here on site.

Our website has a lot more information in addition to the below, but as a few key places of interest:

EATING AND DRINKING NEARBY
- The Gongoozlers Rest - a canal boat cafe at the marina just over the road. Seats around eight inside with outside seating too. Usually weekends only.
- The Admiral Nelson - about a 10 minute walk along the canal. Good food and drink with canalside seating.
- The Old Boat - another canalside pub close by, with a carvery on Sundays.
- Braunston village, next to the marina, has further pubs, a post office and takeaways.

OUT AND ABOUT
There are lovely countryside walks to neighbouring villages such as Willoughby and Ashby St Ledgers, the latter being where the Gunpowder Plot was hatched. Daventry is the nearest town, under 10 minutes' drive, and Rugby is the nicest, around 15-20 minutes away. If there's something particular you're after, just ask and I'll see if I can point you in the right direction.

If you need anything at all during your stay, WhatsApp or call me on 07756 142487.

I hope you have a wonderful stay.

Kind regards,
Alex
Corner House
BODY;

        $templates = [
            ['booking_confirmation', 'Booking confirmation', 'Welcome to {{property}} - your booking {{reference}}', $bookingConfirmationBody],
            ['payment_confirmation', 'Payment confirmation', 'Payment received for {{reference}}', 'Hello {{guest_name}}, we have received payment of £{{total}} for booking {{reference}}.'],
            ['pre_arrival', 'Pre-arrival', 'Your stay at {{property}} starts tomorrow', 'Hello {{guest_name}}, we look forward to welcoming you tomorrow. Check-in is {{check_in}} for {{room}}.'],
            ['check_in', 'Check-in instructions', 'Check-in details for {{reference}}', 'Hello {{guest_name}}, today is check-in day for {{room}}. We will be ready for your arrival.'],
            ['check_out', 'Check-out', 'Check-out for {{reference}}', 'Hello {{guest_name}}, check-out is today. We hope you enjoyed your stay.'],
        ];

        foreach ($templates as [$event, $name, $subject, $body]) {
            CommunicationTemplate::updateOrCreate(
                ['slug' => $event],
                [
                    'name' => $name,
                    'event' => $event,
                    'channel' => 'email',
                    'subject' => $subject,
                    'body' => $body,
                    'is_active' => true,
                ],
            );
        }
    }
}
