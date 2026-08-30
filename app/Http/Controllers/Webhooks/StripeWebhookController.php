<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Payment\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class StripeWebhookController extends Controller
{
    public function __construct(private readonly PaymentService $payments) {}

    public function __invoke(Request $request): Response
    {
        try {
            $this->payments->handleWebhook(
                $request->getContent(),
                (string) $request->header('Stripe-Signature', ''),
            );
        } catch (\Throwable $e) {
            Log::error('Stripe webhook failed', ['message' => $e->getMessage()]);

            return response('Invalid payload', 400);
        }

        return response('ok', 200);
    }
}
