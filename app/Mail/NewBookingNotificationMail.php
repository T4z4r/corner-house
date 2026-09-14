<?php

namespace App\Mail;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewBookingNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $emailBody;

    public function __construct(public Reservation $reservation)
    {
        $this->emailBody = $this->buildBody();
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: "New direct booking — {$this->reservation->reference}");
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
        $r = $this->reservation;
        $guest = $r->guest;
        $guestName = trim(($guest->first_name ?? '').' '.($guest->last_name ?? ''));
        $roomName = $r->room?->name ?? 'Room';
        $propertyName = $r->property?->name ?? 'Corner House';

        $adminUrl = route('admin.reservations.show', $r, true);

        $lines = [
            "A new direct booking has been received on the website.",
            '',
            'Reference: '.$r->reference,
            'Guest: '.($guestName !== '' ? $guestName : 'N/A').($guest?->email ? ' ('.$guest->email.')' : ''),
            'Dates: '.$r->check_in->format('D d M Y').' → '.$r->check_out->format('D d M Y'),
            'Nights: '.$r->check_in->diffInDays($r->check_out),
            'Room: '.$roomName.' — '.$propertyName,
            'Guests: '.$r->guests_count,
            'Total: £'.number_format((float) $r->total_amount, 2),
            '',
            'View the booking in the admin panel:',
            $adminUrl,
        ];

        return implode("\r\n", $lines);
    }
}