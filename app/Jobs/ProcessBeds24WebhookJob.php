<?php

namespace App\Jobs;

use App\Models\ChannelWebhook;
use App\Services\Channel\ChannelManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessBeds24WebhookJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $webhookId) {}

    public function handle(ChannelManager $channels): void
    {
        $webhook = ChannelWebhook::query()->find($this->webhookId);

        if (! $webhook) {
            return;
        }

        $channels->processWebhook($webhook);
    }
}
