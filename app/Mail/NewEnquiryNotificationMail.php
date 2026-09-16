<?php

namespace App\Mail;

use App\Models\Enquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewEnquiryNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $emailBody;

    public function __construct(public Enquiry $enquiry)
    {
        $this->emailBody = $this->buildBody();
    }

    public function envelope(): Envelope
    {
        $subject = $this->enquiry->type === Enquiry::TYPE_BOOKING
            ? 'New booking enquiry from '.$this->enquiry->name
            : 'New website enquiry from '.$this->enquiry->name;

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.guest-communication-html',
            text: 'mail.guest-communication-text',
            with: [
                'emailSubject' => $this->envelope()->subject,
                'emailBody' => $this->emailBody,
            ],
        );
    }

    private function buildBody(): string
    {
        $e = $this->enquiry;

        $lines = [
            $e->type === Enquiry::TYPE_BOOKING
                ? 'A new booking enquiry has come in from the website.'
                : 'A new website enquiry has come in from the contact form.',
            '---',
            'Name: '.$e->name,
            'Email: '.$e->email,
            $e->phone ? 'Phone: '.$e->phone : '',
            $e->guests ? 'Guests: '.$e->guests : '',
            $e->check_in ? 'Check in: '.$e->check_in->format('d M Y') : '',
            $e->check_out ? 'Check out: '.$e->check_out->format('d M Y') : '',
            $e->nights ? 'Nights: '.$e->nights : '',
            $e->drinks_package ? 'Drinks package: requested' : '',
            $e->terms_accepted ? 'Terms and house rules: accepted' : '',
            '---',
            $e->message ?: 'No message.',
            '',
            'View all enquiries in the admin panel:',
            route('admin.enquiries.index'),
        ];

        return implode("\r\n", array_filter($lines));
    }
}
