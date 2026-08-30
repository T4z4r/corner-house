<?php

namespace App\Services\Notification;

use App\Mail\GuestCommunicationMail;
use App\Models\Communication;
use App\Models\CommunicationTemplate;
use App\Models\Reservation;
use App\Models\Setting;
use App\Services\System\MailConfigurationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    public function __construct(private readonly MailConfigurationService $mailConfiguration) {}

    public function sendForEvent(string $event, Reservation $reservation): ?Communication
    {
        if (! $this->shouldSendEvent($event)) {
            Log::info('Email notification disabled for event', ['event' => $event]);

            return null;
        }

        $template = CommunicationTemplate::query()
            ->where('event', $event)
            ->where('is_active', true)
            ->first();

        if (! $template) {
            Log::info('No communication template for event', ['event' => $event]);

            return null;
        }

        $reservation->loadMissing(['guest', 'room', 'property']);
        $recipient = $reservation->guest?->email;

        if (! $recipient) {
            return null;
        }

        $replacements = $this->replacements($reservation);

        $communication = Communication::create([
            'guest_id' => $reservation->guest_id,
            'reservation_id' => $reservation->id,
            'communication_template_id' => $template->id,
            'channel' => $template->channel,
            'recipient' => $recipient,
            'subject' => $this->interpolate($template->subject ?? '', $replacements),
            'body' => $this->interpolate($template->body, $replacements),
            'status' => 'pending',
        ]);

        return $this->dispatch($communication);
    }

    public function sendManual(array $data): Communication
    {
        $communication = Communication::create([
            'guest_id' => $data['guest_id'] ?? null,
            'reservation_id' => $data['reservation_id'] ?? null,
            'communication_template_id' => $data['communication_template_id'] ?? null,
            'channel' => $data['channel'] ?? 'email',
            'direction' => $data['direction'] ?? 'outbound',
            'recipient' => $data['recipient'],
            'sender_name' => $data['sender_name'] ?? null,
            'subject' => $data['subject'] ?? null,
            'body' => $data['body'],
            'status' => 'pending',
            'metadata' => $data['metadata'] ?? null,
        ]);

        return $this->dispatch($communication);
    }

    public function dispatch(Communication $communication): Communication
    {
        try {
            if ($communication->channel === 'email') {
                $this->mailConfiguration->apply();

                Mail::to($communication->recipient)->send(new GuestCommunicationMail(
                    $communication->subject ?: 'Corner House',
                    $communication->body,
                ));
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
     * @return array<string, string>
     */
    private function replacements(Reservation $reservation): array
    {
        return [
            '{{guest_name}}' => $reservation->guest?->full_name ?? 'Guest',
            '{{reference}}' => $reservation->reference,
            '{{check_in}}' => $reservation->check_in->format('d M Y'),
            '{{check_out}}' => $reservation->check_out->format('d M Y'),
            '{{room}}' => $reservation->room?->name ?? '',
            '{{property}}' => $reservation->property?->name ?? '',
            '{{total}}' => number_format((float) $reservation->total_amount, 2),
        ];
    }

    /**
     * @param  array<string, string>  $replacements
     */
    private function interpolate(string $text, array $replacements): string
    {
        return strtr($text, $replacements);
    }

    private function shouldSendEvent(string $event): bool
    {
        if (! (bool) Setting::getValue('email_notifications_enabled', true)) {
            return false;
        }

        return match ($event) {
            'booking_confirmation' => (bool) Setting::getValue('email_booking_confirmation_enabled', true),
            'payment_confirmation' => (bool) Setting::getValue('email_payment_confirmation_enabled', true),
            'pre_arrival' => (bool) Setting::getValue('email_pre_arrival_enabled', true),
            'check_in' => (bool) Setting::getValue('email_check_in_enabled', true),
            'check_out' => (bool) Setting::getValue('email_check_out_enabled', true),
            default => true,
        };
    }
}
