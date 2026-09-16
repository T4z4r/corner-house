NEW DIRECT BOOKING REQUEST
===========================

A new booking request has been submitted through the website.

GUEST
Name:   {{ $enquiry->name }}
Email:  {{ $enquiry->email }}
@if ($enquiry->phone)Phone:  {{ $enquiry->phone }}
@endif
STAY REQUESTED
Room:        {{ $roomName }}
Check in:    {{ $enquiry->check_in->format('D d M Y') }}
Check out:   {{ $enquiry->check_out->format('D d M Y') }}
Nights:      {{ $enquiry->nights }}
Guests:      {{ $enquiry->guests }}
Quoted total: £{{ number_format($total, 2) }}
@if ($enquiry->drinks_package)
Drinks package: requested
@endif
@if ($enquiry->terms_accepted)
Terms and house rules: accepted
@endif

Dates are held until {{ $expiresAt->format('D d M Y \a\t H:i') }} ({{ $holdHours }} hours).
@if ($enquiry->message)

GUEST MESSAGE
{{ $enquiry->message }}
@endif

Corner house, old road, Braunston, Daventry, NN11 7JB
https://cornerhousebraunston.uk