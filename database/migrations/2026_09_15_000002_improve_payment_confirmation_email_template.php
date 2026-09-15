<?php

use App\Models\CommunicationTemplate;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $body = <<<'BODY'
Hello {{guest_name}},

Great news — your payment has gone through and your booking at {{property}} is now confirmed.

Booking reference: {{reference}}
Stay: {{check_in}} to {{check_out}} ({{nights}} nights)
Room: {{room}}
Amount paid: £{{total}}

We can't wait to welcome you. If you have any questions before your stay, just reply to this email.

Warm regards,
{{property}}
BODY;

        CommunicationTemplate::query()
            ->where('event', 'payment_confirmation')
            ->update([
                'subject' => 'Payment received — booking {{reference}} confirmed',
                'body' => $body,
            ]);
    }

    public function down(): void
    {
        CommunicationTemplate::query()
            ->where('event', 'payment_confirmation')
            ->update([
                'subject' => 'Payment received for {{reference}}',
                'body' => 'Hello {{guest_name}}, we have received payment of £{{total}} for booking {{reference}}.',
            ]);
    }
};
