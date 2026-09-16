@extends('mail.layouts.frame')

@section('content')
    <tr>
        <td style="padding:0 48px 18px 48px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                    <td align="center" style="background-color:#f4f1e8; border:1px solid #e3ddcc; border-radius:999px; padding:8px 18px; font-family:Arial, Helvetica, sans-serif; font-size:12px; letter-spacing:1px; text-transform:uppercase; color:#b4552b; font-weight:bold;">
                        Secure payment request
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
                        @if ($isBalancePayment)
                            Thank you for your payment. Please settle the remaining balance for your stay using the secure link below.
                        @else
                            Thanks for choosing Corner House. To confirm your stay, please settle your refundable security deposit using the secure link below.
                        @endif
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    @include('mail.booking.partials.payment-summary')

    <tr>
        <td style="padding:24px 48px 40px 48px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                    <td style="font-family:Arial, Helvetica, sans-serif; font-size:13px; line-height:1.7; color:#6b7268;">
                        Payment is taken securely by Stripe &mdash; Apple Pay, Google Pay and all major cards are accepted. If you have any questions, just reply to this email.
                    </td>
                </tr>
            </table>
        </td>
    </tr>
@endsection
