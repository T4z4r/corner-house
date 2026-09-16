<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guests Export - Corner House</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #1f2937; padding: 2rem; }
        h1 { color: #1f6f43; font-size: 24px; }
        .meta { color: #6b7280; margin-bottom: 1rem; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { text-align: left; padding: 8px; border-bottom: 1px solid #e5e7eb; overflow-wrap: anywhere; }
        th { background: #f3f4f6; }
        tr { break-inside: avoid; }
        button { background: #1f6f43; color: white; border: 0; padding: 8px 16px; border-radius: 6px; cursor: pointer; }
        @media print { body { padding: 0; } .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="no-print"><button onclick="window.print()">Print / Save as PDF</button></div>
    <h1>Corner House - Guests</h1>
    <div class="meta">Generated {{ now()->format('d M Y H:i') }} &middot; {{ $guests->count() }} guests</div>
    @if ($search !== null && $search !== '')
        <p><strong>Search:</strong> {{ $search }}</p>
    @endif
    <table>
        <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Bookings</th><th>Source</th><th>Status</th></tr></thead>
        <tbody>
            @forelse ($guests as $guest)
                <tr>
                    <td>{{ $guest->full_name }}</td>
                    <td>{{ $guest->email ?? '-' }}</td>
                    <td>{{ $guest->phone ?? '-' }}</td>
                    <td>{{ $guest->reservations_count }}</td>
                    <td>{{ $guest->source ?? '-' }}</td>
                    <td>{{ ucfirst($guest->status) }}</td>
                </tr>
            @empty
                <tr><td colspan="6">No guests found</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
