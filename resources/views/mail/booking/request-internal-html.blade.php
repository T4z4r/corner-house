@extends('mail.layouts.frame')

@section('content')
    <tr>
        <td style="padding:0 48px 18px 48px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                    <td align="center" style="background-color:#f4f1e8; border:1px solid #e3ddcc; border-radius:999px; padding:8px 18px; font-family:Arial, Helvetica, sans-serif; font-size:12px; letter-spacing:1px; text-transform:uppercase; color:#b4552b; font-weight:bold;">
                        New direct booking request
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td style="padding:0 48px 6px 48px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                    <td style="font-family:Arial, Helvetica, sans-serif; font-size:15px; line-height:1.7; color:#1e211c;">
                        A new booking request has been submitted through the website.
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    <tr>
        <td style="padding:22px 48px 6px 48px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                    <td style="background-color:#f4f1e8; border:1px solid #e3ddcc; border-radius:8px; padding:18px 22px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                            <tr>
                                <td style="padding-bottom:10px; font-family:Arial, Helvetica, sans-serif; font-size:11px; letter-spacing:1px; text-transform:uppercase; color:#8a8f84;">Guest</td>
                            </tr>
                            <tr>
                                <td style="font-family:Arial, Helvetica, sans-serif; font-size:14px; line-height:1.8; color:#1e211c;">
                                    <strong>{{ $enquiry->name }}</strong><br>
                                    <a href="mailto:{{ $enquiry->email }}" style="color:#2f5136; text-decoration:underline;">{{ $enquiry->email }}</a>@if ($enquiry->phone)
                                        &nbsp;&middot;&nbsp;{{ $enquiry->phone }}
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    <tr>
        <td style="padding:12px 48px 6px 48px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                    <td style="background-color:#f4f1e8; border:1px solid #e3ddcc; border-radius:8px; padding:18px 22px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                            <tr>
                                <td style="padding-bottom:10px; font-family:Arial, Helvetica, sans-serif; font-size:11px; letter-spacing:1px; text-transform:uppercase; color:#8a8f84;">Stay requested</td>
                            </tr>
                            <tr>
                                <td style="padding:8px 0; border-top:1px solid #efead7; font-family:Arial, Helvetica, sans-serif; font-size:13px; color:#1e211c;">
                                    <span style="color:#6b7268;">Room</span><br>
                                    <strong>{{ $roomName }}</strong>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:8px 0; border-top:1px solid #efead7; font-family:Arial, Helvetica, sans-serif; font-size:13px; color:#1e211c;">
                                    <span style="color:#6b7268;">Check in / out</span><br>
                                    <strong>{{ $enquiry->check_in->format('D d M Y') }} &rarr; {{ $enquiry->check_out->format('D d M Y') }}</strong>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:8px 0; border-top:1px solid #efead7; font-family:Arial, Helvetica, sans-serif; font-size:13px; color:#1e211c;">
                                    <span style="color:#6b7268;">Nights &middot; Guests</span><br>
                                    <strong>{{ $enquiry->nights }} night{{ $enquiry->nights == 1 ? '' : 's' }} &middot; {{ $enquiry->guests }}</strong>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:8px 0; border-top:1px solid #efead7; font-family:Arial, Helvetica, sans-serif; font-size:13px; color:#1e211c;">
                                    <span style="color:#6b7268;">Quoted total</span><br>
                                    <strong>&pound;{{ number_format($total, 2) }}</strong>
                                </td>
                            </tr>
                            @if ($enquiry->drinks_package || $enquiry->terms_accepted)
                                <tr>
                                    <td style="padding:8px 0; border-top:1px solid #efead7; font-family:Arial, Helvetica, sans-serif; font-size:13px; color:#1e211c;">
                                        <span style="color:#6b7268;">Details</span><br>
                                        <strong>{!! collect([$enquiry->drinks_package ? 'Drinks package requested' : null, $enquiry->terms_accepted ? 'Terms and house rules accepted' : null])->filter()->join(' &middot; ') !!}</strong>
                                    </td>
                                </tr>
                            @endif
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    <tr>
        <td style="padding:12px 48px 0 48px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                    <td style="background-color:#fdfcf8; border:1px solid #e3ddcc; border-radius:8px; padding:14px 18px; font-family:Arial, Helvetica, sans-serif; font-size:13px; line-height:1.7; color:#6b7268;">
                        <strong style="color:#1f3826;">Dates are held until {{ $expiresAt->format('D d M Y \a\t H:i') }}</strong> ({{ $holdHours }} hours).
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    @if ($enquiry->message)
        <tr>
            <td style="padding:22px 48px 40px 48px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                        <td style="padding-bottom:10px; font-family:Arial, Helvetica, sans-serif; font-size:11px; letter-spacing:1px; text-transform:uppercase; color:#8a8f84;">Guest message</td>
                    </tr>
                    <tr>
                        <td style="background-color:#f4f1e8; border:1px solid #e3ddcc; border-radius:8px; padding:16px 20px; font-family:Georgia, 'Times New Roman', serif; font-size:14px; line-height:1.7; color:#1e211c;">
                            {{ $enquiry->message }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    @else
        <tr>
            <td style="padding:22px 48px 40px 48px;"></td>
        </tr>
    @endif
@endsection