<?php

namespace App\Services\Beds24;

use App\Models\ChannelAccount;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Beds24Client
{
    /** @var list<string> */
    public const ALLOWED_TEST_ENDPOINTS = [
        'authentication/setup',
        'authentication/token',
        'authentication/details',
        'accounts',
        'bookings/invoices',
        'properties',
        'bookings',
        'bookings/messages',
        'inventory/rooms/availability',
        'inventory/rooms/calendar',
        'inventory/rooms/offers',
        'channels/settings',
        'channels/booking/reviews',
        'channels/airbnb',
        'channels/airbnb/users',
        'channels/airbnb/listings',
        'channels/airbnb/reviews',
        'channels/stripe',
        'channels/stripe/paymentMethods',
        'channels/stripe/charges',
        'properties/rooms',
    ];

    /** @var list<string> */
    private const ALLOWED_TEST_METHODS = ['get', 'post', 'patch', 'delete'];

    public function __construct(
        private readonly Beds24AuthService $auth,
        private readonly Beds24RequestLogger $logger,
    ) {}

    public function get(ChannelAccount $account, string $endpoint, array $params = []): array
    {
        return $this->request($account, 'get', $endpoint, $params);
    }

    public function post(ChannelAccount $account, string $endpoint, array $payload = []): array
    {
        return $this->request($account, 'post', $endpoint, $payload);
    }

    public function patch(ChannelAccount $account, string $endpoint, array $payload = []): array
    {
        return $this->request($account, 'patch', $endpoint, $payload);
    }

    public function delete(ChannelAccount $account, string $endpoint, array $payload = []): array
    {
        return $this->request($account, 'delete', $endpoint, $payload);
    }

    /**
     * @return array{status: int, body: array<string, mixed>, limit_remaining: mixed, request_cost: mixed}
     */
    public function test(ChannelAccount $account, string $method, string $endpoint, array $data = []): array
    {
        $method = strtolower($method);
        $endpoint = ltrim($endpoint, '/');

        if (! in_array($method, self::ALLOWED_TEST_METHODS, true) || ! in_array($endpoint, self::ALLOWED_TEST_ENDPOINTS, true)) {
            throw new \InvalidArgumentException('That Beds24 endpoint is not allowed in the test window.');
        }

        return $this->request($account, $method, $endpoint, $data, includeMeta: true);
    }

    /**
     * @return array<string, mixed>
     */
    private function request(ChannelAccount $account, string $method, string $endpoint, array $data = [], bool $includeMeta = false): array
    {
        $url = $this->auth->url($endpoint);
        [$request, $payload] = $this->prepareRequest($account, $method, $endpoint, $data);
        $log = $this->logger->start($account, $method, $endpoint, [
            'headers' => $this->requestHeadersForLog($endpoint, $payload, $account, $method),
            'body' => $payload,
        ]);

        $response = null;
        $responseBody = null;

        try {
            /** @var Response $response */
            $response = match ($method) {
                'get' => $request->get($url, $payload),
                'post' => $request->post($url, $payload),
                'patch' => $request->patch($url, $payload),
                'delete' => $request->delete($url, $payload),
                default => throw new \InvalidArgumentException('Unsupported HTTP method.'),
            };

            $responseBody = $response->json();
            $responseBody = is_array($responseBody) ? $responseBody : ['raw' => $response->body()];

            if ($response->failed()) {
                $failure = new \RuntimeException('Beds24 request failed: '.$response->status());
                $this->logger->failure($log, $endpoint, $failure, $response, $responseBody);
                throw $failure;
            }

            $this->logger->success($log, $endpoint, $response, $responseBody);

            if ($includeMeta) {
                return [
                    'status' => $response->status(),
                    'body' => $responseBody,
                    'credits' => [
                        'limit' => $response->header('X-FiveMinCreditLimit'),
                        'remaining' => $response->header('X-FiveMinCreditLimit-Remaining'),
                        'resets_in' => $response->header('X-FiveMinCreditLimit-ResetsIn'),
                        'request_cost' => $response->header('X-RequestCost'),
                    ],
                ];
            }

            return $responseBody;
        } catch (\Throwable $e) {
            if ($response === null) {
                $this->logger->failure($log, $endpoint, $e);
            }

            Log::error('Beds24 API error', [
                'endpoint' => $endpoint,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * @return array{0: PendingRequest, 1: array<string, mixed>}
     */
    private function prepareRequest(ChannelAccount $account, string $method, string $endpoint, array $data = []): array
    {
        $http = Http::connectTimeout(5)
            ->timeout(25)
            ->acceptJson();

        if ($endpoint === 'authentication/setup') {
            $code = (string) ($data['code'] ?? $data['invite_code'] ?? '');

            if ($code === '') {
                throw new \InvalidArgumentException('Beds24 invite code is required for authentication/setup.');
            }

            return [
                $http->withHeaders(['code' => $code])->retry(2, 500),
                array_diff_key($data, array_flip(['code', 'invite_code'])),
            ];
        }

        if ($endpoint === 'authentication/token') {
            $refreshToken = (string) ($data['refreshToken'] ?? $data['refresh_token'] ?? ($account->credentials['refresh_token'] ?? config('services.beds24.refresh_token') ?? ''));

            if ($refreshToken === '') {
                throw new \InvalidArgumentException('Beds24 refresh token is required for authentication/token.');
            }

            return [
                $http->withHeaders(['refreshToken' => $refreshToken])->retry(2, 500),
                array_diff_key($data, array_flip(['refreshToken', 'refresh_token'])),
            ];
        }

        $token = $this->auth->accessToken($account);

        $http = $http
            ->withHeaders(['token' => $token]);

        if ($method === 'get') {
            $http = $http->retry(2, 500);
        }

        return [$http, $data];
    }

    private function requestHeadersForLog(string $endpoint, array $payload, ChannelAccount $account, string $method): array
    {
        if ($endpoint === 'authentication/setup') {
            return ['code' => (string) ($payload['code'] ?? $payload['invite_code'] ?? '')];
        }

        if ($endpoint === 'authentication/token') {
            return [
                'refreshToken' => (string) ($payload['refreshToken'] ?? $payload['refresh_token'] ?? ($account->credentials['refresh_token'] ?? config('services.beds24.refresh_token') ?? '')),
            ];
        }

        return ['token' => $this->auth->accessToken($account), 'method' => strtoupper($method)];
    }
}
