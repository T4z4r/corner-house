Hi {{ $firstName }},

Thanks for choosing Corner House. To confirm your stay at {{ $roomName }} ({{ $checkIn->format('D d M Y') }} to {{ $checkOut->format('D d M Y') }}), please settle your refundable security deposit of £{{ number_format($deposit, 2) }} here:

{{ $paymentUrl }}

This payment link expires {{ $expiresAt->format('D d M Y \a\t H:i') }} and is valid for {{ $linkHours }} hours.
@if (($balanceDue ?? 0) > 0)
The balance of £{{ number_format($balanceDue, 2) }} is due before your arrival.
@endif

Payment is taken securely by Stripe — Apple Pay, Google Pay and all major cards are accepted. If you have any questions, just reply to this email.

With best wishes,
The Corner House Team

Corner House, Main Street, Braunston, Northamptonshire NN7 7ND