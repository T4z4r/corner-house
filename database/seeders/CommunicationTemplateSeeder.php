<?php

namespace Database\Seeders;

use App\Models\CommunicationTemplate;
use Illuminate\Database\Seeder;

class CommunicationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            ['booking_confirmation', 'Booking confirmation', 'Your Corner House booking {{reference}}', 'Hello {{guest_name}}, your stay at {{property}} from {{check_in}} to {{check_out}} is confirmed. Reference {{reference}}. Total £{{total}}.'],
            ['payment_confirmation', 'Payment confirmation', 'Payment received for {{reference}}', 'Hello {{guest_name}}, we have received payment of £{{total}} for booking {{reference}}.'],
            ['pre_arrival', 'Pre-arrival', 'Your stay at {{property}} starts tomorrow', 'Hello {{guest_name}}, we look forward to welcoming you tomorrow. Check-in is {{check_in}} for {{room}}.'],
            ['check_in', 'Check-in instructions', 'Check-in details for {{reference}}', 'Hello {{guest_name}}, today is check-in day for {{room}}. We will be ready for your arrival.'],
            ['check_out', 'Check-out', 'Check-out for {{reference}}', 'Hello {{guest_name}}, check-out is today. We hope you enjoyed your stay.'],
        ];

        foreach ($templates as [$event, $name, $subject, $body]) {
            CommunicationTemplate::firstOrCreate(
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
