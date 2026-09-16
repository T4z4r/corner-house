<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Audit\AuditLogger;
use App\Services\Payment\InstantPayoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class InstantPayoutController extends Controller
{
    public function index(Request $request): View
    {
        $options = null;
        try {
            $options = app(InstantPayoutService::class)->options();
        } catch (Throwable $exception) {
            report($exception);
        }

        return view('admin.payments.instant-payout', [
            'options' => $options,
            'review' => $request->session()->get('instant_payout_review'),
        ]);
    }

    public function review(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'destination' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'regex:/^\d{1,9}(\.\d{1,2})?$/'],
        ]);

        try {
            $review = app(InstantPayoutService::class)->review($data['destination'], (string) $data['amount']);
            $review['key'] = (string) Str::uuid();
            $review['user_id'] = (int) $request->user()->id;
            $review['expires_at'] = now()->addMinutes(15)->timestamp;
            $request->session()->put('instant_payout_review', $review);
        } catch (\DomainException $exception) {
            return back()->withInput()->withErrors(['payout' => $exception->getMessage()]);
        } catch (Throwable $exception) {
            report($exception);

            return back()->withInput()->withErrors(['payout' => 'Unable to check Stripe payout eligibility. Please try again.']);
        }

        return redirect()->route('admin.payments.instant');
    }

    public function store(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $data = $request->validate(['confirmation' => ['accepted'], 'review_key' => ['required', 'uuid']]);
        $review = $request->session()->get('instant_payout_review');
        abort_unless(is_array($review) && $review['user_id'] === (int) $request->user()->id && hash_equals($review['key'], $data['review_key']), 403);
        if (isset($review['result'])) {
            return redirect()->route('admin.payments.instant')->with('status', 'Payout '.$review['result']['id'].' has already been submitted.');
        }
        if ($review['expires_at'] < now()->timestamp) {
            return back()->withErrors(['payout' => 'This payout review has expired. Check Stripe for any previous attempt before reviewing a new payout.']);
        }

        try {
            $result = app(InstantPayoutService::class)->send($review);
            $review['result'] = $result;
            $request->session()->put('instant_payout_review', $review);
            $auditLogger->log('payments.instant_payout', 'payments', 'payout', $result['id'], newValues: [
                'amount_minor' => $review['amount'], 'currency' => $review['currency'],
                'destination' => $review['destination'], 'status' => $result['status'], 'livemode' => $review['livemode'],
            ]);
        } catch (\DomainException $exception) {
            return back()->withErrors(['payout' => $exception->getMessage()]);
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors(['payout' => 'Stripe could not confirm the payout. Retry this same confirmation to avoid a duplicate, or check the Stripe Dashboard before starting another payout.']);
        }

        return redirect()->route('admin.payments.instant')->with('status', 'Payout '.$result['id'].' submitted to Stripe. Status: '.$result['status'].'.');
    }
}
