<?php

namespace App\Services\System;

use App\Models\Setting;
use Illuminate\Support\Facades\Schema;

class MailConfigurationService
{
    public function apply(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        config([
            'mail.default' => Setting::getValue('mail_mailer', config('mail.default')),
            'mail.mailers.smtp.host' => Setting::getValue('mail_host', config('mail.mailers.smtp.host')),
            'mail.mailers.smtp.port' => Setting::getValue('mail_port', config('mail.mailers.smtp.port')),
            'mail.mailers.smtp.username' => Setting::getValue('mail_username', config('mail.mailers.smtp.username')),
            'mail.mailers.smtp.password' => Setting::getValue('mail_password', config('mail.mailers.smtp.password')),
            'mail.mailers.smtp.scheme' => Setting::getValue('mail_encryption', config('mail.mailers.smtp.scheme')),
            'mail.mailers.smtp.stream' => [
                'ssl' => [
                    'verify_peer' => filter_var(Setting::getValue('mail_ssl_verify_peer', true), FILTER_VALIDATE_BOOLEAN),
                    'verify_peer_name' => filter_var(Setting::getValue('mail_ssl_verify_peer_name', true), FILTER_VALIDATE_BOOLEAN),
                    'allow_self_signed' => filter_var(Setting::getValue('mail_ssl_allow_self_signed', false), FILTER_VALIDATE_BOOLEAN),
                    'cafile' => Setting::getValue('mail_ssl_cafile', null),
                ],
            ],
            'mail.mailers.log.channel' => Setting::getValue('mail_log_channel', config('mail.mailers.log.channel')),
            'mail.from.address' => Setting::getValue('mail_from_address', config('mail.from.address')),
            'mail.from.name' => Setting::getValue('mail_from_name', config('mail.from.name')),
        ]);
    }
}
