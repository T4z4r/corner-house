<!DOCTYPE html>
<html lang="en-GB">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>{{ $emailSubject ?? '' }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f0f3f1; -webkit-text-size-adjust:100%;">
@php
    $brandName = \App\Models\Setting::getValue('property_name', 'Corner House');
    $brandUrl = \App\Models\Setting::getValue('website_url', 'https://cornerhousebraunston.uk');
    $contactEmail = \App\Models\Setting::getValue('website_contact_email', config('mail.from.address'));
    $brandAddress = 'Main Street, Braunston, Northamptonshire NN7 7ND';
@endphp
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f0f3f1;">
    <tr>
        <td align="center" style="padding:24px 12px;">
            <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px; width:100%;">
                <tr>
                    <td style="background-color:#1f6f43; border-radius:12px 12px 0 0; height:6px; line-height:6px;" height="6"></td>
                </tr>
                <tr>
                    <td style="background-color:#ffffff; border-left:1px solid #e5e9ef; border-right:1px solid #e5e9ef;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                            <tr>
                                <td align="center" style="padding:36px 40px 10px 40px;">
                                    <a href="{{ $brandUrl }}" style="text-decoration:none;">
                                        <img src="{{ $message->embed(public_path('images/brand/logo-email.png')) }}" alt="{{ $brandName }}, Braunston" width="300" height="200" style="display:block; width:300px; height:auto; border:0; outline:none; text-decoration:none;">
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td align="center" style="padding:0 40px 28px 40px;">
                                    <table role="presentation" width="64" cellpadding="0" cellspacing="0" border="0">
                                        <tr>
                                            <td style="background-color:#c9a227; height:3px; line-height:3px; border-radius:2px;" height="3"></td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:0 48px 24px 48px; font-family:Arial, Helvetica, sans-serif; font-size:15px; line-height:1.65; color:#1f2937;">
                                    {!! nl2br(e($emailBody)) !!}
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:0 48px 40px 48px; font-family:Arial, Helvetica, sans-serif; font-size:15px; line-height:1.65; color:#1f2937;">
                                    With best wishes,<br>
                                    <strong>The {{ $brandName }} Team</strong>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="background-color:#174f30; border-radius:0 0 12px 12px; padding:30px 40px 32px 40px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                            <tr>
                                <td align="center" style="font-family:Arial, Helvetica, sans-serif; font-size:16px; font-weight:bold; color:#ffffff; padding-bottom:10px;">
                                    {{ $brandName }}
                                </td>
                            </tr>
                            <tr>
                                <td align="center" style="font-family:Arial, Helvetica, sans-serif; font-size:13px; color:#d7e6dc; padding-bottom:4px; line-height:1.6;">
                                    {{ $brandAddress }}
                                </td>
                            </tr>
                            <tr>
                                <td align="center" style="font-family:Arial, Helvetica, sans-serif; font-size:13px; line-height:1.8;">
                                    <a href="{{ $brandUrl }}" style="color:#c9a227; text-decoration:underline;">{{ $brandUrl }}</a>
                                </td>
                            </tr>
                            <tr>
                                <td align="center" style="font-family:Arial, Helvetica, sans-serif; font-size:13px; line-height:1.8;">
                                    <a href="mailto:{{ $contactEmail }}" style="color:#c9a227; text-decoration:underline;">{{ $contactEmail }}</a>
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