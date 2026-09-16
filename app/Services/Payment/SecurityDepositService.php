<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\Reservation;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

class SecurityDepositService
{
    public function __construct(private readonly PaymentGatewayInterface $gateway, private readonly AuditLogger $audit) {}

    public function requestHold(Reservation $reservation): Payment
    {
        return DB::transaction(function () use ($reservation): Payment {
            $reservation = Reservation::query()->lockForUpdate()->findOrFail($reservation->id);
            $this->assertAvailable($reservation);
            $existing = $reservation->payments()->where('metadata->purpose', 'security_deposit')
                ->whereIn('status', ['pending', 'processing', 'paid', 'refunded'])->latest('id')->first();
            if ($existing?->provider_payment_id) {
                $existing = $this->sync($existing);
            }
            if ($existing && $existing->status !== 'cancelled') {
                return $existing;
            }

            $payment = $reservation->payments()->create([
                'guest_id' => $reservation->guest_id,
                'provider' => 'stripe',
                'amount' => $reservation->security_deposit_amount,
                'currency' => $reservation->property?->currency ?? 'GBP',
                'status' => 'pending',
                'metadata' => ['purpose' => 'security_deposit'],
            ]);
            $this->audit->log('payments.hold_requested', 'payments', 'payment', (string) $payment->id);

            return $payment;
        });
    }

    public function guestUrl(Payment $payment): string
    {
        return URL::temporarySignedRoute('booking.security-deposit', now()->addHours(24), ['payment' => $payment]);
    }

    public function prepare(Payment $payment): Payment
    {
        return DB::transaction(function () use ($payment): Payment {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            if (! $payment->isSecurityDeposit()) {
                throw new \DomainException('This is not a security deposit hold.');
            }
            if ($payment->provider_payment_id) {
                return $this->sync($payment);
            }
            $this->assertAvailable($payment->reservation);
            $intent = $this->gateway->createPaymentIntent([
                'amount' => (float) $payment->amount,
                'currency' => $payment->currency,
                'capture_method' => 'manual',
                'idempotency_key' => 'security-deposit-'.$payment->id,
                'description' => 'Security deposit hold for '.$payment->reservation->reference,
                'customer_email' => $payment->reservation->guest?->email,
                'metadata' => ['payment_id' => (string) $payment->id, 'purpose' => 'security_deposit'],
            ]);
            $payment->update([
                'provider_payment_id' => $intent['id'],
                'metadata' => array_merge($payment->metadata, ['client_secret' => $intent['client_secret']]),
            ]);

            return $payment->fresh();
        });
    }

    public function assertAvailable(Reservation $reservation): void
    {
        if ((float) $reservation->security_deposit_amount <= 0) {
            throw new \DomainException('This booking does not have a separate security deposit. Existing charged deposits should be refunded from Payments.');
        }
        if (! in_array($reservation->status, ['confirmed', 'checked_in'], true)) {
            throw new \DomainException('Security deposit holds are available only for confirmed or checked-in bookings.');
        }
        if ($reservation->check_in->gt(today()->addDay()) || $reservation->check_out->lt(today())) {
            throw new \DomainException('Request the security deposit from one day before arrival and before departure. Card holds usually expire within seven days.');
        }
    }

    public function sync(Payment $payment): Payment
    {
        return DB::transaction(function () use ($payment): Payment {
            $locked = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            if (! $locked->isSecurityDeposit() || ! $locked->provider_payment_id) {
                throw new \DomainException('Missing security deposit payment intent.');
            }
            $intent = $this->gateway->retrievePaymentIntent($locked->provider_payment_id);
            if ((int) $intent['amount'] !== (int) round((float) $locked->amount * 100)
                || strtolower($intent['currency'] ?? '') !== strtolower($locked->currency)) {
                throw new \DomainException('Stripe hold amount or currency does not match this deposit.');
            }
            $status = match ($intent['status']) {
                'requires_capture' => 'processing',
                'canceled' => 'cancelled',
                'succeeded' => $locked->status === 'refunded' ? 'refunded' : 'paid',
                default => $locked->status,
            };
            $previousStatus = $locked->status;
            $locked->update([
                'status' => $status,
                'paid_at' => $status === 'paid' ? ($locked->paid_at ?? now()) : $locked->paid_at,
                'metadata' => array_merge($locked->metadata, [
                    'intent_status' => $intent['status'],
                    'capture_before' => $intent['capture_before'] ?? null,
                ]),
            ]);
            if ($status !== $previousStatus) {
                $this->audit->log('payments.hold_'.$status, 'payments', 'payment', (string) $locked->id);
            }

            return $locked->fresh();
        });
    }

    public function release(Payment $payment): void
    {
        DB::transaction(function () use ($payment): void {
            $locked = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            if (! $locked->isSecurityDeposit() || ! $locked->provider_payment_id) {
                throw new \DomainException('This payment is not a security deposit hold.');
            }
            $this->gateway->releaseHold($locked->provider_payment_id);
            $locked->update([
                'status' => 'cancelled',
                'metadata' => array_merge($locked->metadata, ['intent_status' => 'canceled', 'released_at' => now()->toIso8601String()]),
            ]);
            $this->audit->log('payments.hold_released', 'payments', 'payment', (string) $locked->id);
        });
    }
}
