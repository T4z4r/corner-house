<?php

namespace App\Mail;

use App\Models\PaymentLink;
use App\Models\Reservation;
use App\Models\Setting;
use App\Services\Payment\PaymentLinkService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentLinkMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Reservation $reservation,
        public PaymentLink $paymentLink,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Pay for your Corner House stay');
    }

    public function content(): Content
    {
        $deposit = (float) Setting::getValue('damage_deposit', 950);
        $balanceDue = max(0.0, round((float) $this->reservation->total_amount - $deposit, 2));

        return new Content(
            view: 'mail.booking.payment-link-html',
            text: 'mail.booking.payment-link-text',
            with: [
                'reservation' => $this->reservation,
                'roomName' => $this->reservation->room?->name ?? 'Corner House',
                'firstName' => $this->guestFirstName(),
                'checkIn' => $this->reservation->check_in,
                'checkOut' => $this->reservation->check_out,
                'nights' => $this->reservation->check_in->diffInDays($this->reservation->check_out) ?: 1,
                'guests' => $this->reservation->guests_count,
                'deposit' => $deposit,
                'balanceDue' => $balanceDue,
                'paymentUrl' => app(PaymentLinkService::class)->urlFor($this->paymentLink),
                'expiresAt' => $this->paymentLink->expires_at,
                'linkHours' => (int) Setting::getValue('payment_link_hours', 24),
            ],
        );
    }

    private function guestFirstName(): string
    {
        $guest = $this->reservation->guest;
        $firstName = trim((string) ($guest?->first_name ?? ''));

        return $firstName !== '' ? $firstName : 'there';
    }
}
