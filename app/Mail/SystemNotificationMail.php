<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SystemNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $emailBody;

    public function __construct(
        public string $title,
        public string $notificationMessage,
        public ?string $actionUrl = null,
    ) {
        $this->emailBody = $this->buildBody();
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: "[Corner House] {$this->title}");
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
        $lines = [
            $this->notificationMessage,
        ];

        if ($this->actionUrl) {
            $lines[] = '';
            $lines[] = 'View details in admin: '.$this->actionUrl;
        }

        return implode("\r\n", $lines);
    }
}
