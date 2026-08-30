<?php

namespace App\Services\Beds24;

use App\Models\ChannelAccount;
use Illuminate\Support\Facades\Http;

class Beds24AuthService
{
    public function __construct(private readonly Beds24RequestLogger $logger) {}

    public function exchangeInviteCode(ChannelAccount $account, string $code): ChannelAccount
    {
        $log = $this->logger->start($account, 'get', 'authentication/setup', [
            'headers' => ['code' => $code],
            'body' => [],
        ]);

        $response = Http::connectTimeout(5)
            ->timeout(20)
            ->acceptJson()
            ->withHeaders(['code' => $code])
            ->get($this->url('authentication/setup'));

        $body = $response->json();
        $body = is_array($body) ? $body : ['raw' => $response->body()];

        if ($response->failed()) {
            $failure = new \RuntimeException('Beds24 invite code exchange failed ('.$response->status().').');
            $this->logger->failure($log, 'authentication/setup', $failure, $response, $body);

            throw $failure;
        }

        $token = $body['token'] ?? null;
        $refresh = $body['refreshToken'] ?? null;
        $expiresIn = (int) ($body['expiresIn'] ?? 86400);

        if (! is_string($token) || $token === '' || ! is_string($refresh) || $refresh === '') {
            $failure = new \RuntimeException('Beds24 setup response did not include tokens.');
            $this->logger->failure($log, 'authentication/setup', $failure, $response, $body);

            throw $failure;
        }

        $this->logger->success($log, 'authentication/setup', $response, $body);

        $account->update([
            'status' => 'active',
            'last_error' => null,
            'credentials' => [
                'refresh_token' => $refresh,
                'access_token' => $token,
                'access_token_expires_at' => now()->addSeconds(max(60, $expiresIn - 60))->toIso8601String(),
            ],
        ]);

        return $account->fresh();
    }

    public function accessToken(ChannelAccount $account): string
    {
        $credentials = $account->credentials ?? [];
        $access = $credentials['access_token'] ?? null;
        $expiresAt = $credentials['access_token_expires_at'] ?? null;

        if (is_string($access) && $access !== '' && $expiresAt && now()->lt($expiresAt)) {
            return $access;
        }

        $refresh = $credentials['refresh_token'] ?? config('services.beds24.refresh_token');

        if (! is_string($refresh) || $refresh === '') {
            throw new \RuntimeException('Beds24 refresh token is not configured. Exchange an invite code first.');
        }

        $log = $this->logger->start($account, 'get', 'authentication/token', [
            'headers' => ['refreshToken' => $refresh],
            'body' => [],
        ]);

        $response = Http::connectTimeout(5)
            ->timeout(20)
            ->acceptJson()
            ->withHeaders(['refreshToken' => $refresh])
            ->get($this->url('authentication/token'));

        $body = $response->json();
        $body = is_array($body) ? $body : ['raw' => $response->body()];

        if ($response->failed()) {
            $failure = new \RuntimeException('Unable to refresh Beds24 access token ('.$response->status().').');
            $this->logger->failure($log, 'authentication/token', $failure, $response, $body);

            throw $failure;
        }

        $token = $body['token'] ?? ($body['access_token'] ?? null);
        $expiresIn = (int) ($body['expiresIn'] ?? 86400);

        if (! is_string($token) || $token === '') {
            $failure = new \RuntimeException('Beds24 token response did not include an access token.');
            $this->logger->failure($log, 'authentication/token', $failure, $response, $body);

            throw $failure;
        }

        $this->logger->success($log, 'authentication/token', $response, $body);

        $account->update([
            'credentials' => array_merge($credentials, [
                'refresh_token' => $refresh,
                'access_token' => $token,
                'access_token_expires_at' => now()->addSeconds(max(60, $expiresIn - 60))->toIso8601String(),
            ]),
        ]);

        return $token;
    }

    /**
     * GET /authentication/details — token validity, scopes, and diagnostics.
     *
     * @return array{
     *     valid: bool,
     *     token: array<string, mixed>,
     *     diagnostics: array<string, mixed>,
     *     credits: array<string, mixed>,
     *     body: array<string, mixed>
     * }
     */
    public function details(ChannelAccount $account): array
    {
        $access = $this->accessToken($account);

        $log = $this->logger->start($account, 'get', 'authentication/details', [
            'headers' => ['token' => $access],
            'body' => [],
        ]);

        $response = Http::connectTimeout(5)
            ->timeout(20)
            ->acceptJson()
            ->withHeaders(['token' => $access])
            ->get($this->url('authentication/details'));

        $body = $response->json();
        $body = is_array($body) ? $body : ['raw' => $response->body()];

        if ($response->failed()) {
            $message = is_string($body['error'] ?? null) ? $body['error'] : 'Beds24 token details failed ('.$response->status().').';
            $failure = new \RuntimeException($message);
            $this->logger->failure($log, 'authentication/details', $failure, $response, $body);

            throw $failure;
        }

        $token = is_array($body['token'] ?? null) ? $body['token'] : [];
        $diagnostics = is_array($body['diagnostics'] ?? null) ? $body['diagnostics'] : [];
        $valid = (bool) ($body['validToken'] ?? false);

        $credits = [
            'limit' => $response->header('X-FiveMinCreditLimit'),
            'remaining' => $response->header('X-FiveMinCreditLimit-Remaining'),
            'resets_in' => $response->header('X-FiveMinCreditLimit-ResetsIn'),
            'request_cost' => $response->header('X-RequestCost'),
        ];

        $this->logger->success($log, 'authentication/details', $response, $body);

        $account->update([
            'last_error' => $valid ? null : 'Beds24 reported an invalid token.',
            'settings' => array_merge($account->settings ?? [], [
                'token_valid' => $valid,
                'scopes' => $token['scopes'] ?? [],
                'expires_in' => $token['expiresIn'] ?? null,
                'owner_id' => $token['ownerId'] ?? null,
                'device_name' => $token['deviceName'] ?? null,
                'request_ip' => $diagnostics['requestIp'] ?? null,
                'credit_remaining' => $credits['remaining'],
                'last_checked_at' => now()->toIso8601String(),
            ]),
        ]);

        return [
            'valid' => $valid,
            'token' => $token,
            'diagnostics' => $diagnostics,
            'credits' => $credits,
            'body' => $body,
        ];
    }

    public function url(string $endpoint): string
    {
        return rtrim((string) config('services.beds24.api_url'), '/').'/'.ltrim($endpoint, '/');
    }
}
