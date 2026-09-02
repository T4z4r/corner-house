<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookings Export - Corner House</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 12px; color: #1f2937; padding: 2rem; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 2px solid #1f6f43; }
        .header h1 { font-size: 1.5rem; color: #1f6f43; }
        .header .meta { text-align: right; font-size: 11px; color: #6b7280; }
        .filters { background: #f6f8fa; padding: 0.75rem 1rem; border-radius: 6px; margin-bottom: 1rem; font-size: 11px; }
        .filters span { margin-right: 1rem; }
        .filters strong { color: #374151; }
        table { width: 100%; border-collapse: collapse; margin-top: 0.5rem; }
        th { background: #f3f4f6; text-align: left; padding: 8px 10px; font-size: 10px; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; border-bottom: 2px solid #e5e7eb; }
        td { padding: 8px 10px; border-bottom: 1px solid #e5e7eb; font-size: 12px; }
        tr:nth-child(even) { background: #f9fafb; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 10px; font-weight: 600; }
        .badge-success { background: #e7f3ec; color: #1f6f43; }
        .badge-warning { background: #fdf3d7; color: #92610f; }
        .badge-danger { background: #fdecea; color: #b42318; }
        .badge-muted { background: #eef1f4; color: #5b6572; }
        .footer { margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #e5e7eb; font-size: 10px; color: #9ca3af; display: flex; justify-content: space-between; }
        @media print {
            body { padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 1rem; text-align: right;">
        <button onclick="window.print()" style="background: #1f6f43; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 12px;">Print / Save as PDF</button>
    </div>

    <div class="header">
        <div>
            <h1>Corner House - Bookings</h1>
            @if($filters)
                <div class="filters">
                    @if($filters['status'] ?? null)
                        <span><strong>Status:</strong> {{ ucfirst(str_replace('_', ' ', $filters['status'])) }}</span>
                    @endif
                    @if($filters['source'] ?? null)
                        <span><strong>Source:</strong> {{ ucfirst($filters['source']) }}</span>
                    @endif
                    @if($filters['search'] ?? null)
                        <span><strong>Search:</strong> "{{ $filters['search'] }}"</span>
                    @endif
                    @if($filters['check_in_from'] ?? null)
                        <span><strong>Check-in from:</strong> {{ $filters['check_in_from'] }}</span>
                    @endif
                    @if($filters['check_in_to'] ?? null)
                        <span><strong>Check-in to:</strong> {{ $filters['check_in_to'] }}</span>
                    @endif
                </div>
            @endif
        </div>
        <div class="meta">
            <div>{{ $reservations->count() }} booking{{ $reservations->count() === 1 ? '' : 's' }}</div>
            <div>Generated {{ now()->format('d M Y H:i') }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Reference</th>
                <th>Guest</th>
                <th>Room</th>
                <th>Check-in</th>
                <th>Check-out</th>
                <th>Nights</th>
                <th>Guests</th>
                <th>Total</th>
                <th>Paid</th>
                <th>Payment</th>
                <th>Source</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($reservations as $r)
                <tr>
                    <td><strong>{{ $r->reference }}</strong></td>
                    <td>{{ $r->guest?->full_name ?? '-' }}</td>
                    <td>{{ $r->room?->name ?? '-' }}</td>
                    <td>{{ $r->check_in->format('d/m/Y') }}</td>
                    <td>{{ $r->check_out->format('d/m/Y') }}</td>
                    <td>{{ $r->check_in->diffInDays($r->check_out) }}</td>
                    <td>{{ $r->guests_count }}</td>
                    <td>&pound;{{ number_format($r->total_amount, 2) }}</td>
                    <td>&pound;{{ number_format($r->paid_amount, 2) }}</td>
                    <td>
                        <span class="badge {{ $r->payment_status === 'paid' ? 'badge-success' : ($r->payment_status === 'partial' ? 'badge-warning' : 'badge-muted') }}">
                            {{ ucfirst($r->payment_status) }}
                        </span>
                    </td>
                    <td>{{ ucfirst($r->source) }}</td>
                    <td>
                        <span class="badge {{ in_array($r->status, ['confirmed', 'checked_in', 'checked_out']) ? 'badge-success' : (in_array($r->status, ['cancelled', 'no_show']) ? 'badge-danger' : 'badge-warning') }}">
                            {{ ucfirst(str_replace('_', ' ', $r->status)) }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="12" style="text-align: center; padding: 2rem; color: #6b7280;">No bookings found</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <span>Corner House Property Management</span>
        <span>Page 1 of 1</span>
    </div>
</body>
</html>
