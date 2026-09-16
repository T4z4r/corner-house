<?php

namespace App\Services\Notification;

use App\Models\Communication;
use App\Models\CommunicationTemplate;
use App\Models\Enquiry;
use App\Models\Refund;
use App\Models\Reservation;
use App\Models\Setting;
use App\Services\Mail\MailDispatchService;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public function __construct(private readonly MailDispatchService $mailer) {}

    public function sendEnquiryAcknowledgement(Enquiry $enquiry): ?Communication
    {
        if (! $this->shouldSendEvent('enquiry_received') || blank($enquiry->email)) {
            return null;
        }

        $body = "Hello {$enquiry->name},\n\nThank you. Your enquiry has been successfully received and our team will get back to you shortly.";
        if ($enquiry->type === Enquiry::TYPE_BOOKING) {
            $body .= "\n\nThis is an acknowledgement of your enquiry, not a confirmed booking.";
        }

        return $this->sendManual([
            'recipient' => $enquiry->email,
            'subject' => 'We have received your enquiry - Corner House',
            'body' => $body,
            'metadata' => ['event' => 'enquiry_received', 'enquiry_id' => $enquiry->id],
        ]);
    }

    public function prepareBookingDeletion(Reservation $reservation): ?Communication
    {
        if (! $this->shouldSendEvent('booking_deleted') || blank($reservation->guest?->email)) {
            return null;
        }

        return Communication::create([
            'guest_id' => $reservation->guest_id,
            'recipient' => $reservation->guest->email,
            'channel' => 'email',
            'subject' => "Booking {$reservation->reference} deleted",
            'body' => "Hello {$reservation->guest->full_name},\n\nYour booking {$reservation->reference} for {$reservation->check_in->format('d M Y')} to {$reservation->check_out->format('d M Y')} has been deleted. You no longer have a reservation for this stay.\n\nIf you have any questions, please contact our team.",
            'status' => 'pending',
            'metadata' => ['event' => 'booking_deleted', 'reference' => $reservation->reference],
        ]);
    }

    public function prepareRefund(Refund $refund): ?Communication
    {
        $reservation = $refund->payment?->reservation;
        if (! $reservation || ! $this->shouldSendEvent('payment_refund') || blank($reservation->guest?->email)) {
            return null;
        }

        $existing = Communication::query()->where('metadata->refund_id', $refund->id)
            ->where('metadata->event', 'payment_refund')->first();
        if ($existing) {
            return $existing;
        }

        $template = CommunicationTemplate::query()->where('event', 'payment_refund')->first();
        if ($template && ! $template->is_active) {
            return null;
        }

        $replacements = array_merge($this->replacements($reservation), [
            '{{refund_amount}}' => number_format((float) $refund->amount, 2),
            '{{reason_line}}' => blank($refund->reason) ? '' : "\n\nReason: {$refund->reason}\n\n",
        ]);

        return Communication::create([
            'guest_id' => $reservation->guest_id,
            'reservation_id' => $reservation->id,
            'communication_template_id' => $template?->id,
            'channel' => 'email',
            'recipient' => $reservation->guest->email,
            'subject' => $this->interpolate($template?->subject ?? 'Refund initiated for {{reference}}', $replacements),
            'body' => $this->interpolate($template?->body ?? "Hello {{guest_name}},\n\nWe have initiated a refund of £{{refund_amount}} for booking {{reference}}.{{reason_line}}\n\nThe refund will be returned to your original payment method. Your bank may take some time to show it.", $replacements),
            'status' => 'pending',
            'metadata' => ['event' => 'payment_refund', 'refund_id' => $refund->id, 'reference' => $reservation->reference],
        ]);
    }

    public function sendForEvent(string $event, Reservation $reservation, array $extraReplacements = []): ?Communication
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

        // Idempotency: never re-send a template-driven event notification that
        // has already been dispatched for this reservation. Pending deliveries
        // are allowed to be retried after a failure, but a completed send is
        // not duplicated (guard against scheduled-job re-runs and retries).
        $alreadyDelivered = Communication::query()
            ->where('reservation_id', $reservation->id)
            ->where('communication_template_id', $template->id)
            ->whereNotIn('status', ['failed'])
            ->exists();

        if ($alreadyDelivered) {
            Log::info('Event notification already sent for reservation', [
                'event' => $event,
                'reservation_id' => $reservation->id,
            ]);

            return null;
        }

        $reservation->loadMissing(['guest', 'room', 'property']);
        $recipient = $reservation->guest?->email;

        if (! $recipient) {
            return null;
        }

        $replacements = array_merge($this->replacements($reservation), $extraReplacements);

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

        return $this->mailer->send($communication);
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

        return $this->mailer->send($communication);
    }

    /**
     * Manually deliver a template for testing.
     *
     * Unlike sendForEvent(), this ignores the per-event enabled settings and
     * the per-reservation idempotency guard so the same template can be
     * exercised repeatedly. Tokens are rendered against a real reservation
     * when provided, otherwise against sample placeholder values.
     */
    public function sendTest(CommunicationTemplate $template, string $recipient, ?Reservation $reservation = null): Communication
    {
        $replacements = $reservation
            ? array_merge($this->testReplacements(), $this->replacements($reservation))
            : $this->testReplacements();

        $communication = Communication::create([
            'guest_id' => $reservation?->guest_id,
            'reservation_id' => $reservation?->id,
            'communication_template_id' => $template->id,
            'channel' => $template->channel,
            'direction' => 'outbound',
            'recipient' => $recipient,
            'subject' => $this->interpolate($template->subject ?? '', $replacements),
            'body' => $this->interpolate($template->body, $replacements),
            'status' => 'pending',
            'metadata' => ['source' => 'test'],
        ]);

        return $this->mailer->send($communication);
    }

    /**
     * Sample values for every token a template could use, regardless of event.
     *
     * @return array<string, string>
     */
    private function testReplacements(): array
    {
        return [
            '{{guest_name}}' => 'Sample Guest',
            '{{reference}}' => 'CH-000001',
            '{{check_in}}' => 'Fri 01 Jan 2027',
            '{{check_out}}' => 'Mon 04 Jan 2027',
            '{{nights}}' => '3',
            '{{room}}' => 'Lion',
            '{{property}}' => 'Corner House',
            '{{total}}' => '2,850.00',
            '{{refund_amount}}' => '0.00',
            '{{reason_line}}' => '',
        ];
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
            '{{nights}}' => (string) $reservation->check_in->diffInDays($reservation->check_out),
            '{{room}}' => $reservation->room?->name ?? '',
            '{{property}}' => $reservation->property?->name ?? '',
            '{{total}}' => number_format((float) $reservation->total_amount, 2),
            '{{reason_line}}' => '',
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
            'payment_refund' => (bool) Setting::getValue('email_payment_refund_enabled', true),
            'pre_arrival' => (bool) Setting::getValue('email_pre_arrival_enabled', true),
            'check_in' => (bool) Setting::getValue('email_check_in_enabled', true),
            'check_out' => (bool) Setting::getValue('email_check_out_enabled', true),
            default => true,
        };
    }
}
