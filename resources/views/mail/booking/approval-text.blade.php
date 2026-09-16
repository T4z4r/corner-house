Hi {{ $firstName }},

Great news — your booking request for {{ $roomName }} at Corner House has been approved. The dates below are held for you.

YOUR STAY AT {{ strtoupper($roomName) }}
Check in:  {{ $checkIn->format('D d M Y') }}
Check out: {{ $checkOut->format('D d M Y') }}
Nights:    {{ $nights }}
Guests:    {{ $guests }}

To confirm your booking, pay your refundable security deposit of £{{ number_format($deposit, 2) }} here:

{{ $paymentUrl }}

This payment link expires {{ $expiresAt->format('D d M Y \a\t H:i') }} and is valid for {{ $linkHours }} hours.
@if (($balanceDue ?? 0) > 0)
The balance of £{{ number_format($balanceDue, 2) }} is due before your arrival.
@endif

After your deposit is paid we'll send your confirmation and arrival details. Have a question? Just reply to this email.

With best wishes,
The Corner House Team

Corner house, old road, Braunston, Daventry, NN11 7JB
https://cornerhousebraunston.uk