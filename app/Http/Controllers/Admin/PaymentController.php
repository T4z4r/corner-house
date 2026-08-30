<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\Payment\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(private readonly PaymentService $payments) {}

    public function index(Request $request): View
    {
        $query = Payment::query()->with(['reservation.guest'])->latest();

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        return view('admin.payments.index', [
            'payments' => $query->paginate(20)->withQueryString(),
        ]);
    }

    public function show(Payment $payment): View
    {
        return view('admin.payments.show', [
            'payment' => $payment->load(['reservation.guest', 'reservation.room', 'refunds']),
        ]);
    }

    public function refund(Request $request, Payment $payment): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['nullable', 'numeric', 'min:0.01'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->payments->refund(
                $payment,
                isset($data['amount']) ? (float) $data['amount'] : null,
                $data['reason'] ?? null,
                auth()->id(),
            );

            return back()->with('status', 'Refund processed.');
        } catch (\DomainException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
