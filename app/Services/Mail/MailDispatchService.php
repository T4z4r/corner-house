<?php

namespace App\Services\Mail;

use App\Mail\GuestCommunicationMail;
use App\Models\Communication;
use App\Services\System\MailConfigurationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MailDispatchService
{
    public function __construct(private readonly MailConfigurationService $mailConfiguration) {}

    /**
     * Deliver a communication over email.
     *
     * Only email delivery is currently wired up. Unsupported channels are
     * marked as failed with an explicit reason rather than falsely reported
     * as sent.
     */
    public function send(Communication $communication): Communication
    {
        try {
            if ($communication->channel === 'email') {
                $this->mailConfiguration->apply();

                Mail::to($communication->recipient)->send(new GuestCommunicationMail(
                    $communication->subject ?: 'Corner House',
                    $communication->body,
                ));
            } else {
                throw new \DomainException("Unsupported communication channel: {$communication->channel}");
            }

            $communication->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $communication->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            Log::error('Failed to send communication', [
                'id' => $communication->id,
                'message' => $e->getMessage(),
            ]);
        }

        return $communication->fresh();
    }

    /**
     * Re-attempt delivery of a previously failed communication.
     *
     * The failed row is preserved (history is kept) — only its status and the
     * last error are reset before dispatching again. Successful retries mark
     * it sent; another failure records the new error in place.
     */
    public function retry(Communication $communication): Communication
    {
        if ($communication->status !== 'failed') {
            return $communication->fresh();
        }

        $communication->update([
            'status' => 'pending',
            'error_message' => null,
        ]);

        return $this->send($communication->fresh());
    }

    /**
     * Re-deliver a communication as a brand-new record.
     *
     * Unlike a retry, which re-sends the same failed row in place, a resend
     * duplicates the message into a fresh row so the original delivery history
     * is preserved. Only email delivery is supported.
     */
    public function resend(Communication $communication): Communication
    {
        $copy = $communication->replicate();
        $copy->status = 'pending';
        $copy->error_message = null;
        $copy->provider_message_id = null;
        $copy->sent_at = null;
        $copy->metadata = null;
        $copy->save();

        return $this->send($copy);
    }
}
