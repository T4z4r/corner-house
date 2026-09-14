<?php

namespace App\Services\Beds24;

use App\Models\ChannelAccount;
use App\Models\ChannelMapping;
use App\Models\PricingOverride;
use App\Models\PricingRule;
use App\Models\Room;
use App\Services\Pricing\PricingEngine;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use App\Models\Setting;

class Beds24PricingPublisher
{
    public function __construct(
        private readonly Beds24ChannelProvider $provider,
        private readonly PricingEngine $pricing,
    ) {}

    public function postRule(PricingRule $rule): bool
    {
        $account = $this->activeAccount();
        if (! $account instanceof ChannelAccount) {
            return false;
        }

        if (! $rule->start_date || ! $rule->end_date) {
            return false;
        }

        $rooms = $this->targetRoomsForRule($rule);
        if ($rooms->isEmpty()) {
            return false;
        }

        $rows = [];
        foreach ($rooms as $room) {
            $rows = array_merge($rows, $this->rowsForRoomAndRange($account, $room, $rule->start_date, $rule->end_date));
        }

        if ($rows === []) {
            return false;
        }

        try {
            $this->provider->updateRestrictions($account, $rows);

            return true;
        } catch (\Throwable $e) {
            Log::warning('Failed to post pricing rule to Beds24', [
                'pricing_rule_id' => $rule->id,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function postOverride(PricingOverride $override): bool
    {
        $account = $this->activeAccount();
        if (! $account instanceof ChannelAccount) {
            return false;
        }

        $room = $override->room;
        if (! $room instanceof Room) {
            return false;
        }

        $rows = $this->rowsForRoomAndRange($account, $room, $override->start_date, $override->end_date, $override);
        if ($rows === []) {
            return false;
        }

        try {
            $this->provider->updateRestrictions($account, $rows);

            return true;
        } catch (\Throwable $e) {
            Log::warning('Failed to post pricing override to Beds24', [
                'pricing_override_id' => $override->id,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Publish only the configured holiday weekend uplifts (rate + uplift amount)
     * to Beds24 for dates where an uplift applies, skipping non-uplift dates.
     */
    public function postDefaultRates(Carbon $from, Carbon $to): bool
    {
        $account = $this->activeAccount();
        if (! $account instanceof ChannelAccount || $to->lt($from)) {
            return false;
        }

        $rows = [];
        $mappings = ChannelMapping::query()
            ->where('channel_account_id', $account->id)
            ->where('provider', 'beds24')
            ->where('status', 'active')
            ->whereNotNull('external_room_id')
            ->with('room')
            ->get();

        $weekend = (float) Setting::getValue('min_price_weekend', 625);
        $upliftEnabled = (bool) Setting::getValue('holiday_weekend_uplift_enabled', false);
        $uplift = (float) Setting::getValue('holiday_weekend_uplift', 5);

        if (! $upliftEnabled) {
            return false;
        }

        foreach ($mappings as $mapping) {
            if (! $mapping->room instanceof Room) {
                continue;
            }

            for ($date = $from->copy()->startOfDay(); $date->lte($to); $date->addDay()) {
                $isWeekend = in_array($date->dayOfWeek, [Carbon::FRIDAY, Carbon::SATURDAY, Carbon::SUNDAY], true);

                if (! $isWeekend || $this->pricing->dayCategory($date) !== 'uplift') {
                    continue;
                }

                $baseRate = max($weekend, (float) ($mapping->room->base_rate ?? 0));
                $rate = round($baseRate * (1 + ($uplift / 100)), 2);

                $rows[] = [
                    'roomId' => $mapping->external_room_id,
                    'from' => $date->toDateString(),
                    'to' => $date->toDateString(),
                    'price1' => $rate,
                ];
            }
        }

        if ($rows === []) {
            return false;
        }

        try {
            return $this->provider->updateRestrictions($account, $rows);
        } catch (\Throwable $e) {
            Log::warning('Failed to post default Beds24 rates', ['message' => $e->getMessage()]);

            return false;
        }
    }

    private function activeAccount(): ?ChannelAccount
    {
        return ChannelAccount::query()
            ->where('provider', 'beds24')
            ->where('status', 'active')
            ->first();
    }

    /**
     * @return Collection<int, Room>
     */
    private function targetRoomsForRule(PricingRule $rule): Collection
    {
        if ($rule->room_id) {
            return Room::query()->whereKey($rule->room_id)->get();
        }

        if ($rule->property_id) {
            return Room::query()
                ->where('property_id', $rule->property_id)
                ->where('status', 'active')
                ->get();
        }

        return Room::query()
            ->where('status', 'active')
            ->get();
    }

    /**
     * @param  PricingRule|PricingOverride|null  $source
     * @return array<int, array<string, mixed>>
     */
    private function rowsForRoomAndRange(ChannelAccount $account, Room $room, Carbon $start, Carbon $end, mixed $source = null): array
    {
        $externalRoomId = $this->externalRoomIdFor($account, $room);
        if ($externalRoomId === null) {
            return [];
        }

        $longStay = $this->pricing->lengthOfStayDiscountForRoom($room);
        $rows = [];
        $cursor = $start->copy()->startOfDay();
        $final = $end->copy()->startOfDay();

        while ($cursor->lte($final)) {
            $quote = $this->pricing->calculateForRange($room, $cursor, $cursor->copy()->addDay());
            $nightlyRate = (float) ($quote['per_night'][$cursor->toDateString()] ?? $room->base_rate ?? 0);

            if (! $source instanceof PricingOverride && $longStay['pct'] > 0) {
                $nightlyRate = round($nightlyRate * (1 - $longStay['pct'] / 100), 2);
            }

            $rows[] = [
                'roomId' => $externalRoomId,
                'from' => $cursor->toDateString(),
                'to' => $cursor->toDateString(),
                'price1' => $source instanceof PricingOverride
                    ? (float) $source->rate
                    : $nightlyRate,
                'minStay' => $source instanceof PricingOverride
                    ? ($source->minimum_stay ?? $quote['minimum_stay'])
                    : $quote['minimum_stay'],
                'maxStay' => $quote['maximum_stay'] ?? $room->max_stay,
            ];

            $cursor->addDay();
        }

        return $rows;
    }

    private function externalRoomIdFor(ChannelAccount $account, Room $room): ?string
    {
        return ChannelMapping::query()
            ->where('channel_account_id', $account->id)
            ->where('provider', 'beds24')
            ->where('room_id', $room->id)
            ->whereNotNull('external_room_id')
            ->value('external_room_id');
    }
}
