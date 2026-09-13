<?php

namespace App\Services\Beds24;

use App\Mail\Beds24AlertMail;
use App\Models\ChannelAccount;
use App\Models\Setting;
use App\Services\System\MailConfigurationService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class Beds24AlertService
{
    public function __construct(private readonly MailConfigurationService $mailConfiguration) {}

    /**
     * Email the configured recipient when a Beds24 sync run reports errors.
     *
     * Alerts are rate-limited: the same failure signature is only re-sent once
     * the cooldown (settings.beds24_alert_min_interval, default 360 minutes)
     * has elapsed, so a flapping endpoint cannot page the owner every 5 minutes.
     * A different failure signature is always sent immediately, because it
     * signals a new fault rather than a repeat.
     */
    public function notifyFailure(ChannelAccount $account, array $errors): void
    {
        if ($errors === []) {
            return;
        }

        $recipient = Setting::getValue('beds24_alert_email', 'tazarchriss@gmail.com');

        if (! is_string($recipient) || $recipient === '') {
            Log::info('Beds24 alert skipped: no recipient configured');

            return;
        }

        $signature = sha1(implode('|', $errors));
        $settings = $account->settings ?? [];
        $lastSentAt = $settings['beds24_alert_sent_at'] ?? null;
        $lastSignature = $settings['beds24_alert_sig'] ?? '';
        $intervalMinutes = (int) Setting::getValue('beds24_alert_min_interval', 360);

        $newFailure = $lastSignature !== '' && (string) $lastSignature !== $signature;

        if (! $newFailure && $lastSentAt !== null
            && now()->diffInMinutes(Carbon::parse($lastSentAt)) < $intervalMinutes) {
            Log::info('Beds24 alert suppressed by cooldown', [
                'account_id' => $account->id,
                'signature' => $signature,
            ]);

            return;
        }

        try {
            $this->mailConfiguration->apply();

            Mail::to($recipient)->send(new Beds24AlertMail($account, $errors));

            $account->update([
                'settings' => array_merge($settings, [
                    'beds24_alert_sent_at' => now()->toIso8601String(),
                    'beds24_alert_sig' => $signature,
                ]),
            ]);

            Log::info('Beds24 alert sent', ['account_id' => $account->id, 'recipient' => $recipient]);
        } catch (\Throwable $e) {
            Log::error('Failed to send Beds24 alert', ['account_id' => $account->id, 'message' => $e->getMessage()]);
        }
    }
}