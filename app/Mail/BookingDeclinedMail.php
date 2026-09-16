<?php

namespace App\Mail;

use App\Models\Enquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingDeclinedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Enquiry $enquiry) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your Corner House booking request');
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.booking.declined-html',
            text: 'mail.booking.declined-text',
            with: [
                'roomName' => $this->enquiry->room?->name ?? 'Corner House',
                'firstName' => $this->firstName(),
                'checkIn' => $this->enquiry->check_in,
                'checkOut' => $this->enquiry->check_out,
            ],
        );
    }

    private function firstName(): string
    {
        $name = trim((string) $this->enquiry->name);
        $parts = $name !== '' ? preg_split('/\s+/', $name) : [];

        return (string) ($parts[0] ?? 'there');
    }
}
