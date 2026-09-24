@php
    $brandName = \App\Models\Setting::getValue('property_name', 'Corner House');
    $contactEmail = \App\Models\Setting::getValue('website_contact_email', config('mail.from.address'));
    $brandAddress = 'Corner house, old road, Braunston, Daventry, NN11 7JB';
@endphp
<!DOCTYPE html>
<html lang="en-GB">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>{{ $emailSubject ?? '' }}</title>
</head>
<body style="margin:0; padding:0; background-color:#eee8db; -webkit-text-size-adjust:100%;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#eee8db;">
    <tr>
        <td align="center" style="padding:24px 12px;">
            <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px; width:100%;">
                <tr>
                    <td style="background-color:#1f3826; border-radius:12px 12px 0 0; height:8px; line-height:8px;" height="8"></td>
                </tr>
                <tr>
                    <td style="background-color:#ffffff; border-left:1px solid #e3ddcc; border-right:1px solid #e3ddcc;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                            <tr>
                                <td align="center" style="padding:36px 40px 6px 40px;">
                                    <img src="{{ $message->embed(public_path('images/brand/logo-email.png')) }}" alt="{{ $brandName }}, Braunston" width="120" height="80" style="display:block; width:120px; height:auto; max-width:120px; border:0; outline:none; text-decoration:none;">
                                </td>
                            </tr>
                            <tr>
                                <td align="center" style="padding:0 40px 26px 40px; font-family:Georgia, 'Times New Roman', serif; font-size:17px; font-weight:normal; color:#1e211c;">
                                    {{ $brandName }}
                                </td>
                            </tr>
                            <tr>
                                <td align="center" style="padding:0 40px 34px 40px;">
                                    <table role="presentation" width="48" cellpadding="0" cellspacing="0" border="0">
                                        <tr>
                                            <td style="background-color:#b4552b; height:3px; line-height:3px; border-radius:2px;" height="3"></td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            @yield('content')
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="background-color:#1f3826; border-radius:0 0 12px 12px; padding:30px 40px 32px 40px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                            <tr>
                                <td align="center" style="font-family:Georgia, 'Times New Roman', serif; font-size:16px; color:#ffffff; padding-bottom:10px;">
                                    {{ $brandName }}
                                </td>
                            </tr>
                            <tr>
                                <td align="center" style="font-family:Arial, Helvetica, sans-serif; font-size:13px; color:#c9cfc7; padding-bottom:6px; line-height:1.6;">
                                    {{ $brandAddress }}
                                </td>
                            </tr>
                            <tr>
                                <td align="center" style="font-family:Arial, Helvetica, sans-serif; font-size:12px; color:#c9cfc7; padding:0 8px 12px 8px; line-height:1.6;">
                                    Can't see an expected email from us? Please check your junk or spam folder.
                                </td>
                            </tr>
                            <tr>
                                <td align="center" style="font-family:Arial, Helvetica, sans-serif; font-size:13px; line-height:1.8;">
                                    <a href="mailto:{{ $contactEmail }}" style="color:#e8a97b; text-decoration:underline;">{{ $contactEmail }}</a>
                                </td>
                            </tr>
                            <tr>
                                <td align="center" style="font-family:Arial, Helvetica, sans-serif; font-size:13px; line-height:1.8;">
                                    <a href="https://cornerhousebraunston.uk" style="color:#e8a97b; text-decoration:underline;">cornerhousebraunston.uk</a>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
