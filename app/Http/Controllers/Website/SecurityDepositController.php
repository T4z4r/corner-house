<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Setting;
use App\Services\Payment\SecurityDepositService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SecurityDepositController extends Controller
{
    public function show(Request $request, Payment $payment, SecurityDepositService $deposits): View
    {
        abort_unless($payment->isSecurityDeposit(), 404);
        $error = null;
        try {
            $payment = $deposits->prepare($payment);
            if ($payment->status === 'pending') {
                $deposits->assertAvailable($payment->reservation);
            }
        } catch (\DomainException $exception) {
            $error = $exception->getMessage();
        } catch (\Throwable $exception) {
            report($exception);
            $error = 'We could not load your security deposit. Please try again shortly.';
        }

        return view('website.booking.security-deposit', [
            'payment' => $payment,
            'error' => $error,
            'returnUrl' => $request->fullUrlWithoutQuery(['payment_intent', 'payment_intent_client_secret', 'redirect_status']),
            'stripeKey' => Setting::getValue('stripe_key') ?: config('services.stripe.key'),
        ]);
    }
}
