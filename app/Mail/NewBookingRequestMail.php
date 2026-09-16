<?php

namespace App\Mail;

use App\Models\Enquiry;
use App\Models\Room;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class NewBookingRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Enquiry $enquiry,
        public Room $room,
        public Carbon $expiresAt,
        public float $total,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'New booking request (direct)');
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.booking.request-internal-html',
            text: 'mail.booking.request-internal-text',
            with: [
                'enquiry' => $this->enquiry,
                'roomName' => $this->room->name,
                'expiresAt' => $this->expiresAt,
                'holdHours' => (int) Setting::getValue('booking_request_hold_hours', 48),
                'total' => $this->total,
            ],
        );
    }
}
