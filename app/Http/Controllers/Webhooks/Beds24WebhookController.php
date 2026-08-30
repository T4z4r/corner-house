<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessBeds24WebhookJob;
use App\Services\Channel\ChannelManager;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class Beds24WebhookController extends Controller
{
    public function __construct(private readonly ChannelManager $channels) {}

    public function __invoke(Request $request): Response
    {
        $secret = (string) config('services.beds24.webhook_secret');

        if ($secret !== '' && $request->header('X-Beds24-Signature') !== $secret && $request->input('secret') !== $secret) {
            return response('Unauthorized', 401);
        }

        $payload = $request->all();
        $webhook = $this->channels->storeWebhook(
            'beds24',
            $payload,
            $request->input('event') ?? $request->input('type'),
            (string) ($request->input('id') ?? $request->input('bookId') ?? ''),
        );

        ProcessBeds24WebhookJob::dispatch($webhook->id);

        return response('ok', 200);
    }
}
