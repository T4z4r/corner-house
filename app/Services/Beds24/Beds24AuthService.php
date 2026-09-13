<?php

namespace App\Services\Beds24;

use App\Models\ChannelAccount;
use Illuminate\Support\Facades\Http;

class Beds24AuthService
{
    public function __construct(private readonly Beds24RequestLogger $logger) {}

    public function exchangeInviteCode(ChannelAccount $account, string $code): ChannelAccount
    {
        $this->setup($account, $code);

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

        $accountRefresh = $credentials['refresh_token'] ?? null;
        $systemRefresh = config('services.beds24.refresh_token');

        // 1. Try the account's stored refresh token.
        if (is_string($accountRefresh) && $accountRefresh !== '') {
            try {
                return $this->refreshAccessToken($account, $accountRefresh);
            } catch (\Throwable) {
                // Token is dead — fall through to the system-wide fallback below.
            }
        }

        // 2. Fall back to the system-wide refresh token (if it differs from the one just tried).
        if (
            is_string($systemRefresh) && $systemRefresh !== ''
            && ($systemRefresh !== $accountRefresh)
        ) {
            try {
                return $this->refreshAccessToken($account, $systemRefresh);
            } catch (\Throwable) {
                // System token also dead — fall through to invite-code exchange.
            }
        }

        // 3. Last resort: re-exchange a stored invite code.
        $inviteCode = $credentials['invite_code']
            ?? $account->settings['invite_code']
            ?? config('services.beds24.invite_code');

        if (is_string($inviteCode) && $inviteCode !== '') {
            return $this->setup($account, $inviteCode)['token'];
        }

        throw new \RuntimeException('Beds24 refresh token or invite code is not configured. Exchange an invite code first.');
    }

    /**
     * GET /authentication/setup — exchange an invite code for a refresh token
     * and an initial access token. The invite code is stored with the account so
     * later syncs can re-mint tokens automatically whenever the stored refresh
     * token lapses or is missing.
     *
     * @return array{token: string, refresh_token: string, expires_in: int}
     */
    private function setup(ChannelAccount $account, string $code): array
    {
        $log = $this->logger->start($account, 'get', 'authentication/setup', [
            'headers' => ['code' => $code],
            'body' => [],
        ]);

        $response = Http::connectTimeout(5)
            ->timeout(20)
            ->acceptJson()
            ->withHeaders(['code' => $code])
            ->retry(3, 1000)
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
            'credentials' => array_merge($account->credentials ?? [], [
                'invite_code' => $code,
                'refresh_token' => $refresh,
                'access_token' => $token,
                'access_token_expires_at' => now()->addSeconds(max(60, $expiresIn - 60))->toIso8601String(),
            ]),
        ]);

        return [
            'token' => $token,
            'refresh_token' => $refresh,
            'expires_in' => $expiresIn,
        ];
    }

    private function refreshAccessToken(ChannelAccount $account, string $refresh): string
    {
        $credentials = $account->credentials ?? [];

        $log = $this->logger->start($account, 'get', 'authentication/token', [
            'headers' => ['refreshToken' => $refresh],
            'body' => [],
        ]);

        $response = Http::connectTimeout(5)
            ->timeout(20)
            ->acceptJson()
            ->withHeaders(['refreshToken' => $refresh])
            ->retry(3, 1000)
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
