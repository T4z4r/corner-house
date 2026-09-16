@extends('mail.layouts.frame')

@section('content')
    <tr>
        <td style="padding:0 48px 12px 48px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                    <td align="center" style="background-color:#f4f1e8; border:1px solid #e3ddcc; border-radius:999px; padding:8px 18px; font-family:Arial, Helvetica, sans-serif; font-size:12px; letter-spacing:1px; text-transform:uppercase; color:#6b7268; font-weight:bold;">
                        Booking request received
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td style="padding:0 48px 12px 48px; font-family:Georgia, 'Times New Roman', serif; font-size:26px; line-height:1.25; color:#1f3826;">Hi {{ $firstName }},</td>
    </tr>
    <tr>
        <td style="padding:0 48px 6px 48px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                    <td style="font-family:Arial, Helvetica, sans-serif; font-size:15px; line-height:1.7; color:#1e211c;">
                        Thank you for your booking request
                        @if ($checkIn)
                            for {{ $roomName }} from {{ $checkIn->format('D d M Y') }}{{ $checkOut ? ' to '.$checkOut->format('D d M Y') : '' }}
                        @endif
                        .
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td style="padding:0 48px 24px 48px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                    <td style="font-family:Arial, Helvetica, sans-serif; font-size:15px; line-height:1.7; color:#1e211c;">
                        We're very sorry, but we are unable to accept this request at this time, and the dates have now been released.
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td style="padding:0 48px 40px 48px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                    <td style="font-family:Arial, Helvetica, sans-serif; font-size:13px; line-height:1.7; color:#6b7268;">
                        If you'd like to talk through alternative dates, or if there's anything we can do to help, just reply to this email &mdash; we'll always do our best.
                    </td>
                </tr>
            </table>
        </td>
    </tr>
@endsection