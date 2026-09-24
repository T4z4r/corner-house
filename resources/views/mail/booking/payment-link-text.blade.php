Hi {{ $firstName }},

@if ($reservation->security_deposit_amount !== null)
Please pay GBP {{ number_format($paymentAmount, 2) }} for your stay using the secure link below. The security deposit of GBP {{ number_format($reservation->security_deposit_amount, 2) }} is a separate card hold requested near arrival and is not charged here.
@elseif ($isBalancePayment)
Thank you for your payment of £{{ number_format((float) $reservation->paid_amount, 2) }}. Please pay the remaining balance of £{{ number_format($paymentAmount, 2) }} for your stay at {{ $roomName }} ({{ $checkIn->format('D d M Y') }} to {{ $checkOut->format('D d M Y') }}) here:
@else
Thanks for choosing Corner House. To confirm your stay at {{ $roomName }} ({{ $checkIn->format('D d M Y') }} to {{ $checkOut->format('D d M Y') }}), please settle your refundable security deposit of £{{ number_format($deposit, 2) }} here:

@endif

{{ $paymentUrl }}

This payment link expires {{ $expiresAt->format('D d M Y \a\t H:i') }} and is valid for {{ $linkHours }} hours.
@if (($balanceDue ?? 0) > 0)
The balance of £{{ number_format($balanceDue, 2) }} is due before your arrival.
@endif

Payment is taken securely by Stripe — Apple Pay, Google Pay and all major cards are accepted. If you have any questions, just reply to this email.

With best wishes,
The Corner House Team

@include('mail.booking.partials.inbox-reminder-text')

Corner house, old road, Braunston, Daventry, NN11 7JB
https://cornerhousebraunston.uk
