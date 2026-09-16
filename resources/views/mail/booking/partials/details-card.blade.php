@php
    $rows = $detailRows ?? [];
    $pretitle = $cardTitle ?? null;
@endphp
<tr>
    <td style="padding:24px 48px 8px 48px;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
            <tr>
                <td style="background-color:#f4f1e8; border:1px solid #e3ddcc; border-radius:8px; padding:20px 22px;">
                    @if ($pretitle)
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                            <tr>
                                <td style="padding-bottom:12px; font-family:Arial, Helvetica, sans-serif; font-size:11px; letter-spacing:1px; text-transform:uppercase; color:#8a8f84;">{{ $pretitle }}</td>
                            </tr>
                        </table>
                    @endif
                    @if ($rows)
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#fdfcf8; border-radius:6px;">
                            @foreach ($rows as $label => $value)
                                <tr>
                                    <td style="padding:10px 12px; {{ $loop->last ? '' : 'border-bottom:1px solid #efead7;' }}">
                                        <span style="font-family:Arial, Helvetica, sans-serif; font-size:11px; letter-spacing:1px; text-transform:uppercase; color:#6b7268;">{{ $label }}</span><br>
                                        <strong style="font-family:Arial, Helvetica, sans-serif; font-size:14px; color:#1e211c;">{{ $value }}</strong>
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                    @endif
                </td>
            </tr>
        </table>
    </td>
</tr>