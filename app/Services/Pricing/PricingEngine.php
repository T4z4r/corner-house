<?php

namespace App\Services\Pricing;

use App\Models\CalendarBlock;
use App\Models\CompetitorRate;
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
    public function calculateForRange(Room $room, Carbon $checkIn, Carbon $checkOut, int $guests = 1, ?float $occupancyPct = null, bool $isDirectBooking = false): array
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

        if ($isDirectBooking) {
            $discountPct = (float) Setting::getValue('direct_booking_discount', 10);
            $discount = round($base * ($discountPct / 100), 2);
        }

        // Length-of-stay discounts (e.g. 10% from 4 nights, 25% from 7 nights).
        $lengthOfStayPct = $this->lengthOfStayDiscountPercent($room, $checkIn, $checkOut);
        if ($lengthOfStayPct > 0) {
            $discount += round($base * ($lengthOfStayPct / 100), 2);
        }

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
        $requiresLongMinimum = $this->isBankHolidayWeekend($checkIn, $checkOut)
            || $this->isFestivePeriod($checkIn, $checkOut);
        $settingMin = $requiresLongMinimum
            ? (int) Setting::getValue('min_stay_bank_holiday_nights', 3)
            : (int) Setting::getValue('min_stay_nights', 2);
        $minimum = max($settingMin, (int) ($room->min_stay ?: 1));

        $rules = PricingRule::query()
            ->where('is_enabled', true)
            ->where('rule_type', '!=', 'length_of_stay')
            ->whereNotNull('minimum_stay')
            ->where(fn ($q) => $q->whereNull('room_id')->orWhere('room_id', $room->id))
            ->where(fn ($q) => $q->whereNull('property_id')->orWhere('property_id', $room->property_id))
            ->get();

        foreach ($rules as $rule) {
            $overlaps = ($rule->recurring)
                ? $this->rangeTouchesRecurring($rule, $checkIn, $checkOut)
                : ((! $rule->start_date || $checkOut->gt($rule->start_date))
                    && (! $rule->end_date || $checkIn->lte($rule->end_date)));

            if ($overlaps) {
                $minimum = max($minimum, (int) $rule->minimum_stay);
            }
        }

        // Min-stay calendar blocks raise the minimum for the covered range.
        $blockMinimum = CalendarBlock::query()
            ->active()
            ->where('type', 'min_stay')
            ->whereNotNull('min_stay')
            ->where($this->blockScopeFor($room))
            ->whereDate('start_date', '<=', $checkOut->toDateString())
            ->whereDate('end_date', '>=', $checkIn->toDateString())
            ->max('min_stay');

        if ($blockMinimum !== null) {
            $minimum = max($minimum, (int) $blockMinimum);
        }

        return $minimum;
    }

    public function maximumStayForRange(Room $room, Carbon $checkIn, Carbon $checkOut): ?int
    {
        $maximum = $room->max_stay ? (int) $room->max_stay : null;

        $rules = PricingRule::query()
            ->where('is_enabled', true)
            ->where('rule_type', '!=', 'length_of_stay')
            ->whereNotNull('max_stay')
            ->where(fn ($q) => $q->whereNull('room_id')->orWhere('room_id', $room->id))
            ->where(fn ($q) => $q->whereNull('property_id')->orWhere('property_id', $room->property_id))
            ->get();

        foreach ($rules as $rule) {
            $overlaps = ($rule->recurring)
                ? $this->rangeTouchesRecurring($rule, $checkIn, $checkOut)
                : ((! $rule->start_date || $checkOut->gt($rule->start_date))
                    && (! $rule->end_date || $checkIn->lte($rule->end_date)));

            if (! $overlaps) {
                continue;
            }

            $ruleMaximum = (int) $rule->max_stay;
            $maximum = $maximum === null ? $ruleMaximum : min($maximum, $ruleMaximum);
        }

        // Max-stay calendar blocks cap the allowed length of stay.
        $blockMaximum = CalendarBlock::query()
            ->active()
            ->where('type', 'max_stay')
            ->whereNotNull('max_stay')
            ->where($this->blockScopeFor($room))
            ->whereDate('start_date', '<=', $checkOut->toDateString())
            ->whereDate('end_date', '>=', $checkIn->toDateString())
            ->min('max_stay');

        if ($blockMaximum !== null) {
            $maximum = $maximum === null ? (int) $blockMaximum : min($maximum, (int) $blockMaximum);
        }

        return $maximum;
    }

    /**
     * The length-of-stay discount percentage and the night threshold it
     * applies from, based on the largest qualifying tier for the room.
     * Returns 0 when no length-of-stay rule applies.
     *
     * @return array{pct: float, threshold: int}
     */
    public function lengthOfStayDiscountForRoom(Room $room): array
    {
        $rule = PricingRule::query()
            ->where('is_enabled', true)
            ->where('rule_type', 'length_of_stay')
            ->whereNotNull('minimum_stay')
            ->where(fn ($q) => $q->whereNull('room_id')->orWhere('room_id', $room->id))
            ->where(fn ($q) => $q->whereNull('property_id')->orWhere('property_id', $room->property_id))
            ->orderByRaw('minimum_stay desc, abs(adjustment_value) desc')
            ->first();

        if (! $rule || $rule->adjustment_type !== 'percent') {
            return ['pct' => 0.0, 'threshold' => 0];
        }

        return [
            'pct' => abs((float) $rule->adjustment_value),
            'threshold' => (int) $rule->minimum_stay,
        ];
    }

    /**
     * Largest length-of-stay discount tier whose night threshold is met by
     * the stay, expressed as a percent. Returns 0 when no tier qualifies.
     */
    private function lengthOfStayDiscountPercent(Room $room, Carbon $checkIn, Carbon $checkOut): int
    {
        $nights = (int) $checkIn->diffInDays($checkOut);

        $rule = PricingRule::query()
            ->where('is_enabled', true)
            ->where('rule_type', 'length_of_stay')
            ->whereNotNull('minimum_stay')
            ->where('minimum_stay', '<=', $nights)
            ->where(fn ($q) => $q->whereNull('room_id')->orWhere('room_id', $room->id))
            ->where(fn ($q) => $q->whereNull('property_id')->orWhere('property_id', $room->property_id))
            ->orderByRaw('minimum_stay desc, abs(adjustment_value) desc')
            ->first();

        if (! $rule || $rule->adjustment_type !== 'percent') {
            return 0;
        }

        return (int) round(abs((float) $rule->adjustment_value));
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
        $winningTier = null;

        // 2. Calendar price blocks set an explicit nightly rate for the date.
        $blockRate = $this->findPriceBlockRate($room, $date);
        if ($blockRate !== null) {
            $rate = $blockRate;
        } else {
            // 3. Only the highest-priority applicable rule tier applies. Lower-priority
            // tiers (defined by PRIORITY_ORDER) do NOT stack. This matches the
            // pricing priority documented in the platform spec (manual override >
            // event > holiday > seasonal > occupancy > demand > competitor > base).
            $applicable = $this->collectAdjustments($room, $date, $occupancyPct)
                ->sortBy(fn ($a) => self::PRIORITY_ORDER[$a['type']] ?? 99)
                ->first();

            if ($applicable) {
                $winningTier = $applicable['type'];
                $rate = $this->applyAdjustment($rate, $applicable['adjustment_type'], (float) $applicable['adjustment_value'], $applicable['competitor_avg'] ?? null);
            }
        }

        // 4. Optional 5% weekend uplift during UK holiday periods. It is skipped
        // when a manual override, explicit calendar rate, or a fixed event/holiday
        // rule already set the price for the date.
        if (
            $blockRate === null
            && $winningTier !== 'event'
            && $winningTier !== 'holiday'
            && (bool) Setting::getValue('holiday_weekend_uplift_enabled', false)
            && $this->isUKBankHolidayWeekend($date)
        ) {
            $upliftPct = (float) Setting::getValue('holiday_weekend_uplift', 5);
            $rate *= 1 + ($upliftPct / 100);
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
            ->where('rule_type', '!=', 'length_of_stay')
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
                'competitor_avg' => $rule->rule_type === 'competitor'
                    ? $this->averageCompetitorRateForDate($room, $date)
                    : null,
            ]);
        }

        return $adjustments;
    }

    private function ruleApplies(PricingRule $rule, Carbon $date, ?float $occupancyPct): bool
    {
        if ($rule->recurring) {
            if (! $this->recurringCovers($rule, $date)) {
                return false;
            }
        } else {
            if ($rule->start_date && $date->lt($rule->start_date)) {
                return false;
            }
            if ($rule->end_date && $date->gt($rule->end_date)) {
                return false;
            }
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

    private function applyAdjustment(float $rate, string $type, float $value, ?float $competitorAvg = null): float
    {
        if ($type === 'percent') {
            return $rate * (1 + ($value / 100));
        }

        if ($type === 'multiplier') {
            return $rate * $value;
        }

        if ($type === 'relative') {
            // "Match competitor": the rate becomes the latest average competitor
            // rate for the date when competitor data exists, otherwise unchanged.
            return $competitorAvg ?? $rate;
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

    /**
     * Resolve a nightly rate set directly on the calendar for the date.
     * The most recent matching block wins. Multiplier blocks are applied
     * against the room base rate.
     */
    private function findPriceBlockRate(Room $room, Carbon $date): ?float
    {
        $block = CalendarBlock::query()
            ->active()
            ->whereIn('type', ['daily_price', 'fixed_prices', 'multiplier'])
            ->where($this->blockScopeFor($room))
            ->whereDate('start_date', '<=', $date->toDateString())
            ->whereDate('end_date', '>=', $date->toDateString())
            ->orderByDesc('start_date')
            ->first();

        if (! $block || $block->value === null) {
            return null;
        }

        if ($block->type === 'multiplier') {
            return (float) $room->base_rate * (float) $block->value;
        }

        return round((float) $block->value, 2);
    }

    /**
     * Scope matching a room's own blocks plus property-wide blocks
     * (no room_id) for the same property.
     */
    private function blockScopeFor(Room $room): \Closure
    {
        return fn ($q) => $q->where('room_id', $room->id)
            ->orWhere(fn ($q2) => $q2->whereNull('room_id')->where('property_id', $room->property_id));
    }

    /**
     * Average of the most recently captured competitor rate per competitor
     * for the given date, or null when no competitor data exists.
     */
    private function averageCompetitorRateForDate(Room $room, Carbon $date): ?float
    {
        $latestByCompetitor = CompetitorRate::query()
            ->whereDate('date', $date->toDateString())
            ->where(function ($q) use ($room) {
                $q->where('room_id', $room->id)
                    ->orWhere(fn ($q2) => $q2->whereNull('room_id')->where('property_id', $room->property_id));
            })
            ->get()
            ->groupBy('competitor')
            ->map(fn ($group) => $group->sortByDesc('captured_at')->first());

        if ($latestByCompetitor->isEmpty()) {
            return null;
        }

        $rates = $latestByCompetitor->map(fn ($rate) => (float) $rate->rate);

        return round($rates->avg(), 2);
    }

    private function isBankHolidayWeekend(Carbon $checkIn, Carbon $checkOut): bool
    {
        $months = [$checkIn->month, $checkOut->month];

        foreach ($months as $month) {
            $holiday = $this->bankHolidayForMonth($checkIn->year, $month);
            if ($holiday && $holiday->gte($checkIn) && $holiday->lt($checkOut)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Compute the England & Wales bank holiday for a given year and month,
     * using PHP's built-in Easter date plus the fixed/schedule rules observed
     * by the UK government. Returns null when no holiday falls within the month.
     */
    private function bankHolidayForMonth(int $year, int $month): ?Carbon
    {
        foreach ($this->ukBankHolidaysForYear($year) as $holiday) {
            if ($holiday->month === $month) {
                return $holiday;
            }
        }

        return null;
    }

    /**
     * All England & Wales bank holidays for a year, including weekend
     * substitutes (the next weekday when the date falls on a weekend).
     *
     * @return array<int, Carbon>
     */
    private function ukBankHolidaysForYear(int $year): array
    {
        $easter = Carbon::createFromTimestamp(easter_date($year))->startOfDay();

        return [
            $this->nextWeekdayOrSubstitute(Carbon::create($year, 1, 1)),  // New Year's Day
            $easter->copy()->subDays(2),   // Good Friday
            $easter->copy()->addDay(),     // Easter Monday
            $this->firstMondayOfMonth($year, 5),  // Early May
            $this->lastMondayOfMonth($year, 5),   // Spring
            $this->lastMondayOfMonth($year, 8),   // Summer
            $this->nextWeekdayOrSubstitute(Carbon::create($year, 12, 25)), // Christmas
            $this->nextWeekdayOrSubstitute(Carbon::create($year, 12, 26)), // Boxing Day
        ];
    }

    private function isBankHolidayDate(Carbon $date): bool
    {
        foreach ([$date->year, $date->year + 1] as $year) {
            foreach ($this->ukBankHolidaysForYear($year) as $holiday) {
                if ($holiday->eq($date)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Whether the date is a Friday, Saturday or Sunday that forms part of a
     * UK holiday period: a bank-holiday weekend (the holiday or the two
     * nights before it) or the festive weeks around Christmas / New Year.
     */
    private function isUKBankHolidayWeekend(Carbon $date): bool
    {
        if (! in_array($date->dayOfWeek, [Carbon::FRIDAY, Carbon::SATURDAY, Carbon::SUNDAY])) {
            return false;
        }

        $monthDay = $date->format('m-d');
        if ($monthDay >= '12-20' || $monthDay <= '01-02') {
            return true;
        }

        for ($offset = 0; $offset <= 2; $offset++) {
            if ($this->isBankHolidayDate($date->copy()->addDays($offset))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether any night of the stay falls within the festive period
     * (24 December to 1 January), which carries a raised minimum stay.
     */
    private function isFestivePeriod(Carbon $checkIn, Carbon $checkOut): bool
    {
        for ($date = $checkIn->copy(); $date->lt($checkOut); $date->addDay()) {
            $monthDay = $date->format('m-d');
            if ($monthDay >= '12-24' || $monthDay <= '01-01') {
                return true;
            }
        }

        return false;
    }

    private function firstMondayOfMonth(int $year, int $month): Carbon
    {
        $date = Carbon::create($year, $month, 1);
        while ($date->dayOfWeek !== Carbon::MONDAY) {
            $date->addDay();
        }

        return $date->startOfDay();
    }

    private function lastMondayOfMonth(int $year, int $month): Carbon
    {
        $date = Carbon::create($year, $month)->endOfMonth();
        while ($date->dayOfWeek !== Carbon::MONDAY) {
            $date->subDay();
        }

        return $date->startOfDay();
    }

    /**
     * Return the date itself if it's a weekday, otherwise the next weekday
     * (Monday) since a bank holiday falling on a weekend is substituted.
     */
    private function nextWeekdayOrSubstitute(Carbon $date): Carbon
    {
        while (in_array($date->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY])) {
            $date->addDay();
        }

        return $date->startOfDay();
    }

    /**
     * Whether a recurring rule's month/day window covers the given date.
     * Recurring rules use start_date/end_date only for their month and day,
     * so they apply on the same calendar window every year. A window whose
     * end is earlier in the year than its start wraps across the year boundary.
     */
    private function recurringCovers(PricingRule $rule, Carbon $date): bool
    {
        if (! $rule->start_date) {
            return true;
        }

        $start = $rule->start_date->format('m-d');
        $end = $rule->end_date?->format('m-d') ?? $start;
        $target = $date->format('m-d');

        if ($start <= $end) {
            return $target >= $start && $target <= $end;
        }

        return $target >= $start || $target <= $end;
    }

    /**
     * Whether a stay range overlaps a recurring rule's annual window. The
     * window repeats every year, so any checked-in night touching it counts.
     */
    private function rangeTouchesRecurring(PricingRule $rule, Carbon $checkIn, Carbon $checkOut): bool
    {
        for ($date = $checkIn->copy(); $date->lt($checkOut); $date->addDay()) {
            if ($this->recurringCovers($rule, $date)) {
                return true;
            }
        }

        return false;
    }
}
