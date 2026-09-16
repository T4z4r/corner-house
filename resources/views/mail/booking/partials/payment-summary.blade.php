@php
    $roomName = $roomName ?? 'Corner House';
    $nights = $nights ?? 1;
    $guests = $guests ?? 1;
    $deposit = (float) ($deposit ?? \App\Models\Setting::getValue('damage_deposit', 950));
    $balanceDue = (float) ($balanceDue ?? 0);
    $linkHours = (int) ($linkHours ?? \App\Models\Setting::getValue('payment_link_hours', 24));
@endphp
<tr>
    <td style="padding:0 48px 8px 48px;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
            <tr>
                <td style="background-color:#f4f1e8; border:1px solid #e3ddcc; border-radius:8px; padding:20px 22px;">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                        <tr>
                            <td style="padding-bottom:12px; font-family:Arial, Helvetica, sans-serif; font-size:11px; letter-spacing:1px; text-transform:uppercase; color:#8a8f84;">Your stay at {{ $roomName }}</td>
                        </tr>
                        <tr>
                            <td style="padding-bottom:8px; font-family:Arial, Helvetica, sans-serif; font-size:13px; color:#1e211c; background-color:#fdfcf8; border-radius:6px;">
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                    <tr>
                                        <td style="padding:10px 12px; border-bottom:1px solid #efead7;">
                                            <span style="color:#6b7268;">Check in</span><br>
                                            <strong style="color:#1e211c;">{{ $checkIn->format('D d M Y') }}</strong>
                                        </td>
                                        <td width="12" style="padding:10px 0;"></td>
                                        <td style="padding:10px 12px; border-bottom:1px solid #efead7;">
                                            <span style="color:#6b7268;">Check out</span><br>
                                            <strong style="color:#1e211c;">{{ $checkOut->format('D d M Y') }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding:10px 12px;">
                                            <span style="color:#6b7268;">Nights</span><br>
                                            <strong style="color:#1e211c;">{{ $nights }}</strong>
                                        </td>
                                        <td></td>
                                        <td style="padding:10px 12px;">
                                            <span style="color:#6b7268;">Guests</span><br>
                                            <strong style="color:#1e211c;">{{ $guests }}</strong>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </td>
</tr>
<tr>
    <td style="padding:24px 48px 0 48px;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
            <tr>
                <td style="font-family:Arial, Helvetica, sans-serif; font-size:15px; line-height:1.7; color:#1e211c;">
                    @if ($isBalancePayment ?? false)
                        You have already paid <strong>&pound;{{ number_format((float) $reservation->paid_amount, 2) }}</strong>. Pay your <strong>remaining balance of &pound;{{ number_format($paymentAmount, 2) }}</strong> below to complete payment for your booking.
                    @else
                        To confirm your booking, pay your <strong>refundable security deposit of &pound;{{ number_format($deposit, 2) }}</strong> below. Once settled, your dates are locked in and nothing else is due today.
                    @endif
                </td>
            </tr>
        </table>
    </td>
</tr>
<tr>
    <td align="center" style="padding:24px 48px 0 48px;">
        <table role="presentation" cellpadding="0" cellspacing="0" border="0">
            <tr>
                <td style="border-radius:8px; background-color:#b4552b;">
                    <a href="{{ $paymentUrl }}" target="_blank" rel="noopener" style="display:inline-block; padding:14px 34px; font-family:Arial, Helvetica, sans-serif; font-size:15px; font-weight:bold; color:#ffffff; text-decoration:none; border-radius:8px;">Proceed to Pay</a>
                </td>
            </tr>
        </table>
    </td>
</tr>
@if ($balanceDue > 0)
    <tr>
        <td style="padding:12px 48px 0 48px; font-family:Arial, Helvetica, sans-serif; font-size:13px; line-height:1.6; color:#6b7268;">
            The balance of &pound;{{ number_format($balanceDue, 2) }} is due before your arrival.
        </td>
    </tr>
@endif
<tr>
    <td style="padding:12px 48px 0 48px; font-family:Arial, Helvetica, sans-serif; font-size:12px; line-height:1.6; color:#8a8f84;">
        <strong style="color:#b4552b;">Link expires {{ $expiresAt->format('D d M Y \a\t H:i') }}</strong> — this payment link is valid for {{ $linkHours }} hours and can only be used once.
    </td>
</tr>
