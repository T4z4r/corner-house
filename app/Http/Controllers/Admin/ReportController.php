<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $type = $request->query('type', 'revenue');
        $from = $request->query('from', now()->startOfMonth()->toDateString());
        $to = $request->query('to', now()->toDateString());
        $rows = $this->rows($type, $from, $to);

        return view('admin.reports.index', compact('type', 'from', 'to', 'rows'));
    }

    public function export(Request $request): StreamedResponse
    {
        $type = $request->query('type', 'revenue');
        $from = $request->query('from', now()->startOfMonth()->toDateString());
        $to = $request->query('to', now()->toDateString());
        $rows = $this->rows($type, $from, $to);
        $filename = $type.'-report-'.$from.'-'.$to.'.csv';

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            if ($rows !== []) {
                fputcsv($handle, array_keys($rows[0]));
                foreach ($rows as $row) {
                    fputcsv($handle, $row);
                }
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function rows(string $type, string $from, string $to): array
    {
        return match ($type) {
            'occupancy' => Reservation::query()
                ->with(['room', 'property'])
                ->whereNotIn('status', ['cancelled', 'no_show'])
                ->whereDate('check_in', '<=', $to)
                ->whereDate('check_out', '>=', $from)
                ->get()
                ->map(fn (Reservation $reservation): array => [
                    'reference' => $reservation->reference,
                    'property' => $reservation->property?->name,
                    'room' => $reservation->room?->name,
                    'check_in' => $reservation->check_in->toDateString(),
                    'check_out' => $reservation->check_out->toDateString(),
                    'nights' => $reservation->check_in->diffInDays($reservation->check_out),
                    'status' => $reservation->status,
                ])->all(),
            'bookings' => Reservation::query()
                ->with('guest')
                ->whereDate('created_at', '>=', $from)
                ->whereDate('created_at', '<=', $to)
                ->get()
                ->map(fn (Reservation $reservation): array => [
                    'reference' => $reservation->reference,
                    'guest' => $reservation->guest?->full_name,
                    'source' => $reservation->source,
                    'status' => $reservation->status,
                    'total' => $reservation->total_amount,
                ])->all(),
            'cancellations' => Reservation::query()
                ->where('status', 'cancelled')
                ->whereDate('cancelled_at', '>=', $from)
                ->whereDate('cancelled_at', '<=', $to)
                ->get()
                ->map(fn (Reservation $reservation): array => [
                    'reference' => $reservation->reference,
                    'source' => $reservation->source,
                    'total' => $reservation->total_amount,
                    'cancelled_at' => optional($reservation->cancelled_at)->toDateTimeString(),
                ])->all(),
            'payments' => Payment::query()
                ->with('reservation')
                ->whereDate('created_at', '>=', $from)
                ->whereDate('created_at', '<=', $to)
                ->get()
                ->map(fn (Payment $payment): array => [
                    'reference' => $payment->reservation?->reference,
                    'amount' => $payment->amount,
                    'status' => $payment->status,
                    'provider' => $payment->provider,
                    'paid_at' => optional($payment->paid_at)->toDateTimeString(),
                ])->all(),
            default => Reservation::query()
                ->whereIn('status', ['confirmed', 'checked_in', 'checked_out'])
                ->whereDate('check_in', '<=', $to)
                ->whereDate('check_out', '>=', $from)
                ->get()
                ->map(fn (Reservation $reservation): array => [
                    'reference' => $reservation->reference,
                    'source' => $reservation->source,
                    'check_in' => $reservation->check_in->toDateString(),
                    'total' => $reservation->total_amount,
                    'payment_status' => $reservation->payment_status,
                ])->all(),
        };
    }
}
