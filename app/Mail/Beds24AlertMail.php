<?php

namespace App\Mail;

use App\Models\ChannelAccount;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class Beds24AlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $emailBody;

    public function __construct(
        public ChannelAccount $account,
        public array $errors,
    ) {
        $this->emailBody = $this->buildBody();
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Beds24 sync alert — {$this->account->name}");
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
            "The Beds24 channel sync for {$this->account->name} reported the following problems:",
            '',
        ];

        foreach ($this->errors as $error) {
            $lines[] = '- '.$error;
        }

        $lines[] = '';
        $lines[] = 'The account has been flagged for review in the admin panel under Channels. The next scheduled sync run will automatically retry it, so no action is needed unless the problem persists.';

        return implode("\r\n", $lines);
    }
}