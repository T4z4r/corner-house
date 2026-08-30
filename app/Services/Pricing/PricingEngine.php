<?php

namespace App\Services\Pricing;

use App\Models\PricingOverride;
use App\Models\PricingRule;
use App\Models\Room;
use App\Models\Setting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PricingEngine
{
    /**
     * Pricing priority order (lowest number = highest precedence).
     * Manual override > special event > holiday > seasonal > occupancy > demand > competitor > base rate.
     */
    private const PRIORITY_ORDER = [
        'event' => 1,
        'holiday' => 2,
        'seasonal' => 3,
        'occupancy' => 4,
        'demand' => 5,
        'competitor' => 6,
        'last_minute' => 6,
    ];

    public const TAX_RATE = 0.0;

    /**
     * Calculate the total price for a stay, including per-night breakdown.
     *
     * @return array{
     *     total: float,
     *     base_amount: float,
     *     discount_amount: float,
     *     tax_amount: float,
     *     fees_amount: float,
     *     nights: int,
     *     maximum_stay: int|null,
     *     per_night: array<string, float>
     * }
     */
    public function calculateForRange(Room $room, Carbon $checkIn, Carbon $checkOut, int $guests = 1, ?float $occupancyPct = null): array
    {
        if ($checkOut->lte($checkIn)) {
            throw new \DomainException('Check-out must be after check-in.');
        }

        $nights = (int) $checkIn->diffInDays($checkOut);
        $perNight = [];

        for ($i = 0; $i < $nights; $i++) {
            $date = $checkIn->copy()->addDays($i);
            $perNight[$date->toDateString()] = $this->calculateRateForDate($room, $date, $guests, $occupancyPct);
        }

        $base = array_sum($perNight);
        $discount = 0.0;
        $tax = round(($base - $discount) * self::TAX_RATE, 2);
        $cleaningFee = (float) Setting::getValue('cleaning_fee', 50);

        return [
            'total' => round($base - $discount + $tax + $cleaningFee, 2),
            'base_amount' => round($base, 2),
            'discount_amount' => round($discount, 2),
            'tax_amount' => $tax,
            'fees_amount' => $cleaningFee,
            'nights' => $nights,
            'maximum_stay' => $this->maximumStayForRange($room, $checkIn, $checkOut),
            'per_night' => $perNight,
            'minimum_stay' => $this->minimumStayForRange($room, $checkIn, $checkOut),
        ];
    }

    public function minimumStayForRange(Room $room, Carbon $checkIn, Carbon $checkOut): int
    {
        $minimum = (int) ($room->min_stay ?: 1);

        $rules = PricingRule::query()
            ->where('is_enabled', true)
            ->whereNotNull('minimum_stay')
            ->where(fn ($q) => $q->whereNull('room_id')->orWhere('room_id', $room->id))
            ->where(fn ($q) => $q->whereNull('property_id')->orWhere('property_id', $room->property_id))
            ->get();

        foreach ($rules as $rule) {
            $overlaps = (! $rule->start_date || $checkOut->gt($rule->start_date))
                && (! $rule->end_date || $checkIn->lte($rule->end_date));

            if ($overlaps) {
                $minimum = max($minimum, (int) $rule->minimum_stay);
            }
        }

        return $minimum;
    }

    public function maximumStayForRange(Room $room, Carbon $checkIn, Carbon $checkOut): ?int
    {
        $maximum = $room->max_stay ? (int) $room->max_stay : null;

        $rules = PricingRule::query()
            ->where('is_enabled', true)
            ->whereNotNull('max_stay')
            ->where(fn ($q) => $q->whereNull('room_id')->orWhere('room_id', $room->id))
            ->where(fn ($q) => $q->whereNull('property_id')->orWhere('property_id', $room->property_id))
            ->get();

        foreach ($rules as $rule) {
            $overlaps = (! $rule->start_date || $checkOut->gt($rule->start_date))
                && (! $rule->end_date || $checkIn->lte($rule->end_date));

            if (! $overlaps) {
                continue;
            }

            $ruleMaximum = (int) $rule->max_stay;
            $maximum = $maximum === null ? $ruleMaximum : min($maximum, $ruleMaximum);
        }

        return $maximum;
    }

    /**
     * Calculate the rate for a single night, applying rules by priority.
     */
    public function calculateRateForDate(Room $room, Carbon $date, int $guests = 1, ?float $occupancyPct = null): float
    {
        // 1. Manual override (highest priority)
        $override = $this->findOverride($room, $date);
        if ($override) {
            return (float) $override->rate;
        }

        $baseRate = (float) $room->base_rate;
        $rate = $baseRate;

        // Only the highest-priority applicable rule tier applies. Lower-priority
        // tiers (defined by PRIORITY_ORDER) do NOT stack. This matches the
        // pricing priority documented in the platform spec (manual override >
        // event > holiday > seasonal > occupancy > demand > competitor > base).
        $applicable = $this->collectAdjustments($room, $date, $occupancyPct)
            ->sortBy(fn ($a) => self::PRIORITY_ORDER[$a['type']] ?? 99)
            ->first();

        if ($applicable) {
            $rate = $this->applyAdjustment($rate, $applicable['adjustment_type'], (float) $applicable['adjustment_value']);
        }

        // Enforce minimum price floors
        $minWeekday = (float) Setting::getValue('min_price_weekday', 450);
        $minWeekend = (float) Setting::getValue('min_price_weekend', 600);
        $isWeekend = in_array($date->dayOfWeek, [Carbon::FRIDAY, Carbon::SATURDAY, Carbon::SUNDAY]);
        $minPrice = $isWeekend ? $minWeekend : $minWeekday;

        return round(max($minPrice, $rate), 2);
    }

    /**
     * Assess competing rules for a date, returning applicable adjustments.
     */
    private function collectAdjustments(Room $room, Carbon $date, ?float $occupancyPct): Collection
    {
        $rules = PricingRule::query()
            ->where('is_enabled', true)
            ->where(fn ($q) => $q->whereNull('room_id')->orWhere('room_id', $room->id))
            ->where(fn ($q) => $q->whereNull('property_id')->orWhere('property_id', $room->property_id))
            ->get();

        $adjustments = collect();

        foreach ($rules as $rule) {
            if (! $this->ruleApplies($rule, $date, $occupancyPct)) {
                continue;
            }

            $adjustments->push([
                'type' => $rule->rule_type,
                'priority' => (int) $rule->priority,
                'adjustment_type' => $rule->adjustment_type,
                'adjustment_value' => $rule->adjustment_value,
                'minimum_stay' => $rule->minimum_stay,
            ]);
        }

        return $adjustments;
    }

    private function ruleApplies(PricingRule $rule, Carbon $date, ?float $occupancyPct): bool
    {
        if ($rule->start_date && $date->lt($rule->start_date)) {
            return false;
        }
        if ($rule->end_date && $date->gt($rule->end_date)) {
            return false;
        }
        if ($rule->apply_weekends_only && ! in_array($date->dayOfWeek, [Carbon::FRIDAY, Carbon::SATURDAY, Carbon::SUNDAY])) {
            return false;
        }
        if ($rule->rule_type === 'occupancy' && $occupancyPct !== null && $rule->occupancy_threshold !== null) {
            if ($occupancyPct < $rule->occupancy_threshold) {
                return false;
            }
        }
        if ($rule->rule_type === 'last_minute' && $rule->days_before_checkin !== null) {
            $daysAhead = now()->startOfDay()->diffInDays($date, false);
            if ($daysAhead > $rule->days_before_checkin) {
                return false;
            }
        }

        return true;
    }

    private function applyAdjustment(float $rate, string $type, float $value): float
    {
        if ($type === 'percent') {
            return $rate * (1 + ($value / 100));
        }

        if ($type === 'multiplier') {
            return $rate * $value;
        }

        // fixed
        return $rate + $value;
    }

    private function findOverride(Room $room, Carbon $date): ?PricingOverride
    {
        return PricingOverride::query()
            ->where('room_id', $room->id)
            ->where('is_enabled', true)
            ->whereDate('start_date', '<=', $date->toDateString())
            ->whereDate('end_date', '>=', $date->toDateString())
            ->orderBy('start_date', 'desc')
            ->first();
    }
}
