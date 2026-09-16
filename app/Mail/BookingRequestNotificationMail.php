<?php

namespace App\Mail;

use App\Models\Enquiry;
use App\Models\Room;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingRequestNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Enquiry $enquiry,
        public Room $room,
        public Carbon $expiresAt,
        public array $quote,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'New booking request — '.$this->enquiry->name);
    }

    public function content(): Content
    {
        $enquiry = $this->enquiry;
        $deposit = (float) Setting::getValue('damage_deposit', 950);

        $details = ['Guest' => $enquiry->name, 'Email' => $enquiry->email];

        if ($enquiry->phone) {
            $details['Phone'] = $enquiry->phone;
        }

        $details += [
            'Room' => $this->room->name,
            'Check in' => $enquiry->check_in?->format('D d M Y'),
            'Check out' => $enquiry->check_out?->format('D d M Y'),
            'Nights' => $enquiry->nights,
            'Guests' => $enquiry->guests,
            'Quoted total' => '£'.number_format((float) $this->quote['total'], 2),
        ];

        if ($enquiry->drinks_package) {
            $details['Drinks package'] = 'Requested';
        }

        if ($enquiry->terms_accepted) {
            $details['Rental agreement'] = 'Terms and house rules accepted';
        }

        return new Content(
            view: 'mail.booking.notification-html',
            text: 'mail.booking.notification-text',
            with: [
                'emailSubject' => $this->envelope()->subject,
                'pillLabel' => 'New booking request',
                'heading' => 'A new direct booking request has arrived',
                'leadText' => $enquiry->name.' is requesting '.$this->room->name.' at Corner House from '
                    .$enquiry->check_in?->format('D d M Y').' to '.$enquiry->check_out?->format('D d M Y')
                    .'. The dates are held until '.$this->expiresAt->format('D d M Y H:i').'.',
                'detailRows' => $details,
                'cardTitle' => 'Guest details',
                'message' => $enquiry->message ?: null,
                'actionUrl' => route('admin.enquiries.index'),
                'actionLabel' => 'View enquiry',
                'footerNote' => 'Follow up: verify the guest\'s photo ID and signed rental agreement, then email '
                    .'them a secure payment link to take the refundable £'.number_format($deposit, 0).' deposit.',
            ],
        );
    }
}