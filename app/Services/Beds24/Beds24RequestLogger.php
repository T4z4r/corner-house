<?php

namespace App\Services\Beds24;

use App\Models\ChannelAccount;
use App\Models\ChannelSyncLog;
use Illuminate\Http\Client\Response;

class Beds24RequestLogger
{
    /**
     * @param  array<string, mixed>  $request
     */
    public function start(ChannelAccount $account, string $method, string $endpoint, array $request = []): ChannelSyncLog
    {
        return ChannelSyncLog::create([
            'channel_account_id' => $account->id,
            'channel' => 'beds24',
            'operation' => strtoupper($method).' '.$endpoint,
            'request' => $this->redactPayload($endpoint, $request),
            'status' => 'pending',
            'started_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $body
     */
    public function success(ChannelSyncLog $log, string $endpoint, Response $response, array $body): void
    {
        $log->update([
            'status' => 'success',
            'response' => $this->buildResponsePayload($endpoint, $response, $body),
            'completed_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $body
     */
    public function failure(ChannelSyncLog $log, string $endpoint, \Throwable $e, ?Response $response = null, ?array $body = null): void
    {
        $update = [
            'status' => 'failed',
            'error_message' => $e->getMessage(),
            'completed_at' => now(),
        ];

        if ($response && $body !== null) {
            $update['response'] = $this->buildResponsePayload($endpoint, $response, $body);
        } elseif ($response) {
            $update['response'] = $this->buildResponsePayload($endpoint, $response, ['raw' => $response->body()]);
        }

        $log->update($update);
    }

    /**
     * @param  array<string, mixed>  $request
     * @return array<string, mixed>
     */
    private function redactPayload(string $endpoint, array $request): array
    {
        if ($request === []) {
            return [];
        }

        $redacted = $this->redactRecursive($request);

        if (in_array($endpoint, ['authentication/setup', 'authentication/token'], true)) {
            return $redacted;
        }

        return $redacted;
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private function buildResponsePayload(string $endpoint, Response $response, array $body): array
    {
        $body = $this->redactRecursive($body, $endpoint);

        return [
            'status' => $response->status(),
            'headers' => $this->normaliseHeaders($response->headers()),
            'body' => $this->summarise($body),
        ];
    }

    /**
     * @param  array<string, mixed>  $value
     * @return array<string, mixed>
     */
    private function redactRecursive(array $value, ?string $endpoint = null): array
    {
        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = $this->redactRecursive($item, $endpoint);

                continue;
            }

            if ($this->shouldRedactKey((string) $key, $endpoint)) {
                $value[$key] = '[redacted]';
            }
        }

        return $value;
    }

    private function shouldRedactKey(string $key, ?string $endpoint = null): bool
    {
        $key = strtolower($key);

        return in_array($key, ['code', 'invite_code', 'refreshtoken', 'refresh_token', 'token', 'access_token'], true)
            || ($endpoint !== null && in_array($endpoint, ['authentication/setup', 'authentication/token'], true) && in_array($key, ['token', 'refresh_token', 'code', 'invite_code'], true));
    }

    /**
     * @param  array<string, mixed>  $headers
     * @return array<string, mixed>
     */
    private function normaliseHeaders(array $headers): array
    {
        $normalised = [];

        foreach ($headers as $key => $values) {
            if (is_array($values) && count($values) === 1) {
                $normalised[$key] = $values[0];

                continue;
            }

            $normalised[$key] = $values;
        }

        return $normalised;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function summarise(array $payload): array
    {
        $encoded = json_encode($payload);

        if (is_string($encoded) && strlen($encoded) > 4000) {
            return ['truncated' => true, 'keys' => array_keys($payload)];
        }

        return $payload;
    }
}
