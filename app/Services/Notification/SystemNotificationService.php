<?php

namespace App\Services\Notification;

use App\Mail\SystemNotificationMail;
use App\Models\Communication;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\Reservation;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

/**
 * In-app system notifications.
 *
 * URLs are stored relative (/admin/...) because notifications are only ever
 * rendered inside the admin, on the same origin they were created on. A stored
 * absolute URL bakes in whatever host the notification was generated under
 * (e.g. APP_URL fallbacks) and breaks once the app is served elsewhere.
 */
class SystemNotificationService
{
    public function reservationCreated(Reservation $reservation, ?int $actorId = null): void
    {
        $this->broadcast(
            title: $reservation->status === 'confirmed' ? 'Reservation confirmed' : 'Reservation created',
            message: sprintf(
                'Reservation %s for %s has been saved.',
                $reservation->reference,
                $reservation->guest?->full_name ?? 'a guest',
            ),
            url: route('admin.reservations.show', $reservation, false),
            level: $reservation->status === 'confirmed' ? 'success' : 'info',
            icon: 'bi-calendar-check',
            actorId: $actorId,
            metadata: [
                'reservation_id' => $reservation->id,
                'reference' => $reservation->reference,
            ],
        );
    }

    public function reservationUpdated(Reservation $reservation, ?int $actorId = null): void
    {
        $this->broadcast(
            title: 'Reservation updated',
            message: sprintf('Reservation %s has been updated.', $reservation->reference),
            url: route('admin.reservations.show', $reservation, false),
            level: 'info',
            icon: 'bi-calendar-check',
            actorId: $actorId,
            metadata: [
                'reservation_id' => $reservation->id,
                'reference' => $reservation->reference,
            ],
        );
    }

    public function reservationCancelled(Reservation $reservation, ?int $actorId = null): void
    {
        $this->broadcast(
            title: 'Reservation cancelled',
            message: sprintf('Reservation %s was cancelled.', $reservation->reference),
            url: route('admin.reservations.show', $reservation, false),
            level: 'warning',
            icon: 'bi-calendar-x',
            actorId: $actorId,
            metadata: [
                'reservation_id' => $reservation->id,
                'reference' => $reservation->reference,
            ],
        );
    }

    public function reservationCheckedIn(Reservation $reservation, ?int $actorId = null): void
    {
        $this->broadcast(
            title: 'Guest checked in',
            message: sprintf('Reservation %s is now checked in.', $reservation->reference),
            url: route('admin.reservations.show', $reservation, false),
            level: 'info',
            icon: 'bi-door-open',
            actorId: $actorId,
            metadata: [
                'reservation_id' => $reservation->id,
                'reference' => $reservation->reference,
            ],
        );
    }

    public function reservationCheckedOut(Reservation $reservation, ?int $actorId = null): void
    {
        $this->broadcast(
            title: 'Guest checked out',
            message: sprintf('Reservation %s is now checked out.', $reservation->reference),
            url: route('admin.reservations.show', $reservation, false),
            level: 'info',
            icon: 'bi-door-closed',
            actorId: $actorId,
            metadata: [
                'reservation_id' => $reservation->id,
                'reference' => $reservation->reference,
            ],
        );
    }

    public function paymentMarkedPaid(Payment $payment, ?int $actorId = null): void
    {
        $reservation = $payment->reservation;

        if (! $reservation) {
            return;
        }

        $this->broadcast(
            title: 'Payment received',
            message: sprintf(
                'Payment of %s %s for reservation %s has been marked paid.',
                $payment->currency,
                number_format((float) $payment->amount, 2),
                $reservation->reference,
            ),
            url: route('admin.payments.show', $payment, false),
            level: 'success',
            icon: 'bi-credit-card',
            actorId: $actorId,
            metadata: [
                'payment_id' => $payment->id,
                'reservation_id' => $reservation->id,
                'reference' => $reservation->reference,
            ],
        );
    }

    public function paymentRefunded(Payment $payment, Refund $refund, ?int $actorId = null): void
    {
        $reservation = $payment->reservation;

        if (! $reservation) {
            return;
        }

        $this->broadcast(
            title: 'Payment refunded',
            message: sprintf(
                'A refund of %s %s was processed for reservation %s.',
                $payment->currency,
                number_format((float) $refund->amount, 2),
                $reservation->reference,
            ),
            url: route('admin.payments.show', $payment, false),
            level: 'warning',
            icon: 'bi-arrow-counterclockwise',
            actorId: $actorId,
            metadata: [
                'payment_id' => $payment->id,
                'refund_id' => $refund->id,
                'reservation_id' => $reservation->id,
                'reference' => $reservation->reference,
            ],
        );
    }

    public function communicationQueued(Communication $communication, ?int $actorId = null): void
    {
        $this->broadcast(
            title: 'Message queued',
            message: sprintf(
                'A %s message was queued for %s.',
                Str::lower($communication->channel),
                $communication->recipient,
            ),
            url: route('admin.communications.index', [], false),
            level: 'info',
            icon: 'bi-chat-dots',
            actorId: $actorId,
            metadata: [
                'communication_id' => $communication->id,
                'channel' => $communication->channel,
            ],
        );
    }

    public function communicationRetried(Communication $communication, ?int $actorId = null): void
    {
        $this->broadcast(
            title: $communication->status === 'sent' ? 'Message sent' : 'Message retry failed',
            message: sprintf(
                'A failed %s message to %s was retried%s.',
                Str::lower($communication->channel),
                $communication->recipient,
                $communication->status === 'sent' ? ' and sent successfully' : '',
            ),
            url: route('admin.communications.index', [], false),
            level: $communication->status === 'sent' ? 'success' : 'warning',
            icon: 'bi-arrow-repeat',
            actorId: $actorId,
            metadata: [
                'communication_id' => $communication->id,
                'channel' => $communication->channel,
            ],
        );
    }

    public function communicationResent(Communication $communication, ?int $actorId = null): void
    {
        $this->broadcast(
            title: $communication->status === 'sent' ? 'Message resent' : 'Resend failed',
            message: sprintf(
                'A %s message to %s was resent%s.',
                Str::lower($communication->channel),
                $communication->recipient,
                $communication->status === 'sent' ? ' and delivered' : ' but failed again',
            ),
            url: route('admin.communications.index', [], false),
            level: $communication->status === 'sent' ? 'success' : 'warning',
            icon: 'bi-send',
            actorId: $actorId,
            metadata: [
                'communication_id' => $communication->id,
                'channel' => $communication->channel,
            ],
        );
    }

    /**
     * @return Collection<int, User>
     */
    public function recipients(?int $actorId = null): Collection
    {
        return User::query()
            ->whereHas('roles')
            ->when($actorId, fn ($query) => $query->whereKeyNot($actorId))
            ->get();
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function broadcast(
        string $title,
        string $message,
        ?string $url = null,
        string $level = 'info',
        string $icon = 'bi-bell',
        ?int $actorId = null,
        array $metadata = [],
    ): void {
        $recipients = $this->recipients($actorId);

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new SystemNotification(
                title: $title,
                message: $message,
                url: $url,
                level: $level,
                icon: $icon,
                metadata: $metadata,
            ));
        }

        $this->sendNotificationEmail($title, $message, $url);
    }

    private function sendNotificationEmail(string $title, string $message, ?string $url = null): void
    {
        if (! (bool) Setting::getValue('email_notifications_enabled', true)) {
            return;
        }

        $recipient = trim((string) (Setting::getValue('admin_notification_email') ?: Setting::getValue('booking_notify_email', '')));

        if ($recipient === '') {
            return;
        }

        try {
            Mail::to($recipient)->send(new SystemNotificationMail($title, $message, $url));
        } catch (\Throwable $e) {
            Log::warning('Failed to send system notification email', [
                'recipient' => $recipient,
                'title' => $title,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
