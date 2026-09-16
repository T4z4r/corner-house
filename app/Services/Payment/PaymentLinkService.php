<?php

namespace App\Services\Payment;

use App\Models\PaymentLink;
use App\Models\Reservation;
use App\Models\Setting;
use Illuminate\Support\Carbon;

class PaymentLinkService
{
    /**
     * Create a payment link for a reservation, expiring after the configured
     * number of hours (default 24, `payment_link_hours`).
     */
    public function createForReservation(Reservation $reservation, ?Carbon $expiresAt = null, ?int $createdBy = null): PaymentLink
    {
        $hours = max(1, (int) Setting::getValue('payment_link_hours', 24));

        return PaymentLink::create([
            'reservation_id' => $reservation->id,
            'expires_at' => $expiresAt ?? now()->addHours($hours),
            'created_by' => $createdBy,
        ]);
    }

    /**
     * The most recently created, still-active payment link for a reservation.
     */
    public function activeFor(Reservation $reservation): ?PaymentLink
    {
        return $reservation->paymentLinks()
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();
    }

    public function urlFor(PaymentLink $paymentLink): string
    {
        return route('booking.pay-link', $paymentLink->token);
    }
}
