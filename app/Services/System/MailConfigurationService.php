<?php

namespace App\Services\System;

use App\Models\Setting;
use Illuminate\Support\Facades\Schema;

class MailConfigurationService
{
    public function apply(): void
    {
        if (! Schema::hasTable('settings') || ! class_exists(Setting::class)) {
            return;
        }

        config([
            'mail.default' => $this->resolve('mail_mailer', config('mail.default')),
            'mail.mailers.smtp.host' => $this->resolve('mail_host', config('mail.mailers.smtp.host')),
            'mail.mailers.smtp.port' => $this->resolve('mail_port', config('mail.mailers.smtp.port')),
            'mail.mailers.smtp.username' => $this->resolve('mail_username', config('mail.mailers.smtp.username')),
            'mail.mailers.smtp.password' => $this->resolve('mail_password', config('mail.mailers.smtp.password')),
            'mail.mailers.smtp.scheme' => $this->resolveScheme(config('mail.mailers.smtp.scheme')),
            'mail.mailers.smtp.stream' => [
                'ssl' => [
                    'verify_peer' => filter_var($this->resolve('mail_ssl_verify_peer', true), FILTER_VALIDATE_BOOLEAN),
                    'verify_peer_name' => filter_var($this->resolve('mail_ssl_verify_peer_name', true), FILTER_VALIDATE_BOOLEAN),
                    'allow_self_signed' => filter_var($this->resolve('mail_ssl_allow_self_signed', false), FILTER_VALIDATE_BOOLEAN),
                    'cafile' => $this->resolve('mail_ssl_cafile', config('mail.mailers.smtp.stream.ssl.cafile')),
                ],
            ],
            'mail.mailers.log.channel' => $this->resolve('mail_log_channel', config('mail.mailers.log.channel')),
            'mail.from.address' => $this->resolve('mail_from_address', config('mail.from.address')),
            'mail.from.name' => $this->resolve('mail_from_name', config('mail.from.name')),
        ]);
    }

    /**
     * A blank (or whitespace-only) stored value means "inherit the default" —
     * these settings fall back to the given default instead of clobbering the
     * environment configuration with an empty string.
     */
    private function resolve(string $key, mixed $default): mixed
    {
        $value = Setting::getValue($key, $default);

        if (is_string($value) && trim($value) === '') {
            return $default;
        }

        return $value;
    }

    /**
     * Normalise the stored encryption value into a Symfony Mailer scheme.
     *
     * Symfony only knows "smtp" and "smtps" as SMTP schemes. The admin uses
     * the legacy "ssl"/"tls" wording: "ssl" is implicit TLS (port 465) and
     * maps to "smtps"; "tls" is STARTTLS (port 587) and maps to "smtp".
     */
    private function resolveScheme(mixed $default): string
    {
        $value = strtolower((string) $this->resolve('mail_encryption', $default));

        return match ($value) {
            'ssl', 'smtps' => 'smtps',
            'tls', 'smtp' => 'smtp',
            default => (string) ($default === null ? 'smtp' : $default),
        };
    }
}
