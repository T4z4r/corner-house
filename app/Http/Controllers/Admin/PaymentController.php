<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Reservation;
use App\Services\Payment\PaymentService;
use App\Services\Payment\SecurityDepositService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $query = Payment::query()->with(['reservation.guest', 'guest'])->latest();

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $stats = (object) [
            'total_revenue' => (float) Payment::query()->where('status', 'paid')->sum('amount'),
            'paid' => Payment::query()->where('status', 'paid')->count(),
            'pending' => Payment::query()->where('status', 'pending')->count(),
            'refunded' => Payment::query()->where('status', 'refunded')->count(),
        ];

        $stripeBalance = null;
        try {
            $stripeBalance = app(PaymentService::class)->balance();
        } catch (\Throwable $exception) {
            report($exception);
        }

        return view('admin.payments.index', [
            'payments' => $query->paginate(20)->withQueryString(),
            'stats' => $stats,
            'stripeBalance' => $stripeBalance,
        ]);
    }

    public function show(Payment $payment, SecurityDepositService $deposits): View
    {
        if ($payment->isSecurityDeposit() && $payment->provider_payment_id) {
            try {
                $payment = $deposits->sync($payment);
            } catch (\Throwable $exception) {
                report($exception);
                session()->flash('error', 'Unable to refresh the hold status from Stripe. Try again before relying on this status.');
            }
        }

        return view('admin.payments.show', [
            'payment' => $payment->load(['reservation.guest', 'reservation.room', 'refunds']),
            'holdUrl' => $payment->isSecurityDeposit() && $payment->status === 'pending' ? $deposits->guestUrl($payment) : null,
        ]);
    }

    public function requestHold(Reservation $reservation, SecurityDepositService $deposits): RedirectResponse
    {
        try {
            $payment = $deposits->requestHold($reservation);

            return redirect()->route('admin.payments.show', $payment)->with('status', 'Security deposit link ready to share with the guest.');
        } catch (\DomainException $exception) {
            return back()->withErrors(['error' => $exception->getMessage()]);
        }
    }

    public function releaseHold(Payment $payment, SecurityDepositService $deposits): RedirectResponse
    {
        try {
            $deposits->release($payment);

            return back()->with('status', 'Security deposit hold released. The guest’s bank controls when the funds become available.');
        } catch (\DomainException $exception) {
            return back()->withErrors(['error' => $exception->getMessage()]);
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withErrors(['error' => 'The hold could not be released. Refresh its Stripe status and try again.']);
        }
    }

    public function destroy(Payment $payment): RedirectResponse
    {
        try {
            app(PaymentService::class)->deletePending($payment);
        } catch (\DomainException $exception) {
            return back()->withErrors(['error' => $exception->getMessage()]);
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withErrors(['error' => 'The payment could not be deleted. Check its Stripe status and try again.']);
        }

        return redirect()->route('admin.payments.index')->with('status', 'Pending payment deleted.');
    }

    public function refund(Request $request, Payment $payment, PaymentService $payments): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['nullable', 'numeric', 'min:0.01'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $payments->refund(
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
