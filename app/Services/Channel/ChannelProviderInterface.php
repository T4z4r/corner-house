<?php

namespace App\Services\Channel;

use App\Models\ChannelAccount;

interface ChannelProviderInterface
{
    public function provider(): string;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function syncBookings(ChannelAccount $account): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function syncAvailability(ChannelAccount $account): array;

    /**
     * @param  array<int, array<string, mixed>>  $availability
     */
    public function pushAvailability(ChannelAccount $account, array $availability): bool;

    /**
     * @param  array<int, array<string, mixed>>  $rates
     */
    public function pushRates(ChannelAccount $account, array $rates): bool;

    /**
     * @param  array<string, mixed>  $restrictions
     */
    public function updateRestrictions(ChannelAccount $account, array $restrictions): bool;

    /**
     * @param  array<string, mixed>  $message
     */
    public function sendMessage(ChannelAccount $account, array $message): bool;

    /**
     * Fetch guest/channel messages.
     *
     * @return array<int, array<string, mixed>>
     */
    public function fetchMessages(ChannelAccount $account, array $params = []): array;
}
