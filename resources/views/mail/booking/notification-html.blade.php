@extends('mail.layouts.frame')

@section('content')
    @if (! empty($pillLabel))
        <tr>
            <td style="padding:0 48px 18px 48px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                        <td align="center" style="background-color:#f4f1e8; border:1px solid #e3ddcc; border-radius:999px; padding:8px 18px; font-family:Arial, Helvetica, sans-serif; font-size:12px; letter-spacing:1px; text-transform:uppercase; color:#b4552b; font-weight:bold;">
                            {{ $pillLabel }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    @endif

    @if (! empty($heading))
        <tr>
            <td style="padding:0 48px 12px 48px; font-family:Georgia, 'Times New Roman', serif; font-size:26px; line-height:1.25; color:#1f3826;">{{ $heading }}</td>
        </tr>
    @endif

    @if (! empty($leadText))
        <tr>
            <td style="padding:0 48px 6px 48px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                        <td style="font-family:Arial, Helvetica, sans-serif; font-size:15px; line-height:1.7; color:#1e211c;">{{ $leadText }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    @endif

    @if (! empty($detailRows))
        @include('mail.booking.partials.details-card', ['detailRows' => $detailRows, 'cardTitle' => $cardTitle ?? null])
    @endif

    @if (! empty($message))
        <tr>
            <td style="padding:0 48px 6px 48px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                        <td style="background-color:#f4f1e8; border:1px solid #e3ddcc; border-radius:8px; padding:14px 18px; font-family:Arial, Helvetica, sans-serif; font-size:13px; line-height:1.6; color:#1e211c;">
                            <span style="font-size:11px; letter-spacing:1px; text-transform:uppercase; color:#8a8f84;">Message</span><br>
                            {{ $message }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    @endif

    @if (! empty($actionUrl))
        <tr>
            <td align="center" style="padding:24px 48px 12px 48px;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                        <td style="border-radius:8px; background-color:#b4552b;">
                            <a href="{{ $actionUrl }}" target="_blank" rel="noopener" style="display:inline-block; padding:14px 34px; font-family:Arial, Helvetica, sans-serif; font-size:15px; font-weight:bold; color:#ffffff; text-decoration:none; border-radius:8px;">{{ $actionLabel ?? 'View' }} &nbsp;&rarr;</a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    @endif

    @if (! empty($footerNote))
        <tr>
            <td style="padding:0 48px 40px 48px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                        <td style="font-family:Arial, Helvetica, sans-serif; font-size:13px; line-height:1.7; color:#6b7268;">{{ $footerNote }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    @endif
@endsection