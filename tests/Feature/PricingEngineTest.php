<?php

namespace Tests\Feature;

use App\Models\CalendarBlock;
use App\Models\CompetitorRate;
use App\Models\PricingOverride;
use App\Models\PricingRule;
use App\Models\Property;
use App\Models\Room;
use App\Models\Setting;
use App\Services\Pricing\PricingEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PricingEngineTest extends TestCase
{
    use RefreshDatabase;

    private PricingEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = app(PricingEngine::class);

        // Disable minimum price floors and cleaning fee for engine logic tests
        Setting::firstOrCreate(['key' => 'min_price_weekday'], ['value' => '0', 'group' => 'booking', 'label' => 'Min weekday', 'cast' => 'decimal:2']);
        Setting::firstOrCreate(['key' => 'min_price_weekend'], ['value' => '0', 'group' => 'booking', 'label' => 'Min weekend', 'cast' => 'decimal:2']);
        Setting::firstOrCreate(['key' => 'cleaning_fee'], ['value' => '0', 'group' => 'booking', 'label' => 'Cleaning', 'cast' => 'decimal:2']);
    }

    private function makeRoom(float $baseRate): Room
    {
        $property = Property::factory()->create();

        return Room::factory()->create([
            'property_id' => $property->id,
            'base_rate' => $baseRate,
        ]);
    }

    public function test_base_rate_without_rules(): void
    {
        $room = $this->makeRoom(100);
        $rate = $this->engine->calculateRateForDate($room, now()->addDays(10));

        $this->assertSame(100.0, $rate);
    }

    public function test_seasonal_percent_adjustment_is_applied(): void
    {
        $room = $this->makeRoom(100);
        $date = now()->addDays(20);

        PricingRule::create([
            'room_id' => $room->id,
            'name' => 'Winter off-season',
            'rule_type' => 'seasonal',
            'start_date' => $date->copy()->startOfMonth(),
            'end_date' => $date->copy()->endOfMonth(),
            'adjustment_type' => 'percent',
            'adjustment_value' => -20,
            'priority' => 10,
        ]);

        $this->assertSame(80.0, $this->engine->calculateRateForDate($room, $date));
    }

    public function test_event_has_higher_priority_than_seasonal(): void
    {
        $room = $this->makeRoom(100);
        $date = now()->addDays(20);

        PricingRule::create([
            'room_id' => $room->id,
            'name' => 'Seasonal -20%',
            'rule_type' => 'seasonal',
            'start_date' => $date->copy()->startOfMonth(),
            'end_date' => $date->copy()->endOfMonth(),
            'adjustment_value' => -20,
            'priority' => 10,
        ]);

        PricingRule::create([
            'room_id' => $room->id,
            'name' => 'Grand Prix +100%',
            'rule_type' => 'event',
            'start_date' => $date->copy()->subDay(),
            'end_date' => $date->copy()->addDay(),
            'adjustment_value' => 100,
            'priority' => 5,
        ]);

        // Event (+100%) is applied after seasonal because event has higher priority
        // than seasonal, so only event applies => 200.
        $this->assertSame(200.0, $this->engine->calculateRateForDate($room, $date));
    }

    public function test_manual_override_beats_all_rules(): void
    {
        $room = $this->makeRoom(100);
        $date = now()->addDays(5);

        PricingOverride::create([
            'room_id' => $room->id,
            'start_date' => $date->copy()->subDay(),
            'end_date' => $date->copy()->addDay(),
            'rate' => 75,
            'is_enabled' => true,
        ]);

        $this->assertSame(75.0, $this->engine->calculateRateForDate($room, $date));
    }

    public function test_multiplier_adjustment_is_applied(): void
    {
        $room = $this->makeRoom(100);
        $date = now()->addDays(7);

        PricingRule::create([
            'room_id' => $room->id,
            'name' => 'Peak week multiplier',
            'rule_type' => 'seasonal',
            'start_date' => $date->copy()->subDay(),
            'end_date' => $date->copy()->addDay(),
            'adjustment_type' => 'multiplier',
            'adjustment_value' => 1.25,
            'priority' => 5,
        ]);

        $this->assertSame(125.0, $this->engine->calculateRateForDate($room, $date));
    }

    public function test_maximum_stay_takes_the_lowest_applicable_limit(): void
    {
        $room = $this->makeRoom(100);
        $room->update(['max_stay' => 5]);
        $checkIn = now()->addDays(10);
        $checkOut = $checkIn->copy()->addDays(3);

        PricingRule::create([
            'room_id' => $room->id,
            'name' => 'Max stay 4 nights',
            'rule_type' => 'seasonal',
            'start_date' => $checkIn->copy()->subDay(),
            'end_date' => $checkOut->copy()->addDay(),
            'max_stay' => 4,
            'adjustment_type' => 'amount',
            'adjustment_value' => 0,
            'priority' => 5,
        ]);

        $this->assertSame(4, $this->engine->maximumStayForRange($room, $checkIn, $checkOut));
    }

    public function test_occupancy_rule_applies_only_above_threshold(): void
    {
        $room = $this->makeRoom(100);
        $date = now()->addDays(10);

        PricingRule::create([
            'room_id' => $room->id,
            'name' => 'High occupancy +25%',
            'rule_type' => 'occupancy',
            'adjustment_value' => 25,
            'occupancy_threshold' => 80,
            'priority' => 5,
        ]);

        $this->assertSame(100.0, $this->engine->calculateRateForDate($room, $date, occupancyPct: 50));
        $this->assertSame(125.0, $this->engine->calculateRateForDate($room, $date, occupancyPct: 90));
    }

    public function test_calculate_for_range_totals_nightly_rates(): void
    {
        $room = $this->makeRoom(100);
        $checkIn = now()->addDays(30)->startOfDay();
        $checkOut = $checkIn->copy()->addDays(3);

        $result = $this->engine->calculateForRange($room, $checkIn, $checkOut);

        $this->assertSame(3, $result['nights']);
        $this->assertSame(300.0, $result['total']);
        $this->assertSame(300.0, $result['base_amount']);
        $this->assertCount(3, $result['per_night']);
    }

    public function test_last_minute_rule_applies_within_window(): void
    {
        $room = $this->makeRoom(100);
        $date = now()->addDays(1);

        PricingRule::create([
            'room_id' => $room->id,
            'name' => 'Last minute -10%',
            'rule_type' => 'last_minute',
            'adjustment_value' => -10,
            'days_before_checkin' => 3,
            'priority' => 5,
        ]);

        $this->assertSame(90.0, $this->engine->calculateRateForDate($room, $date));
        $this->assertSame(100.0, $this->engine->calculateRateForDate($room, now()->addDays(10)));
    }

    public function test_daily_price_calendar_block_sets_nightly_rate(): void
    {
        $room = $this->makeRoom(100);
        $date = now()->addDays(10);

        CalendarBlock::create([
            'property_id' => $room->property_id,
            'room_id' => $room->id,
            'type' => 'daily_price',
            'value' => 250,
            'start_date' => $date->copy()->subDay(),
            'end_date' => $date->copy()->addDay(),
        ]);

        $this->assertSame(250.0, $this->engine->calculateRateForDate($room, $date));
    }

    public function test_inactive_price_block_is_ignored(): void
    {
        $room = $this->makeRoom(100);
        $date = now()->addDays(10);

        CalendarBlock::create([
            'property_id' => $room->property_id,
            'room_id' => $room->id,
            'type' => 'daily_price',
            'value' => 250,
            'start_date' => $date->copy()->subDay(),
            'end_date' => $date->copy()->addDay(),
            'is_active' => false,
        ]);

        $this->assertSame(100.0, $this->engine->calculateRateForDate($room, $date));
    }

    public function test_multiplier_calendar_block_applies_to_base_rate(): void
    {
        $room = $this->makeRoom(100);
        $date = now()->addDays(10);

        CalendarBlock::create([
            'property_id' => $room->property_id,
            'room_id' => $room->id,
            'type' => 'multiplier',
            'value' => 1.25,
            'start_date' => $date->copy()->subDay(),
            'end_date' => $date->copy()->addDay(),
        ]);

        $this->assertSame(125.0, $this->engine->calculateRateForDate($room, $date));
    }

    public function test_minimum_stay_calendar_block_raises_minimum(): void
    {
        $room = $this->makeRoom(100);
        $checkIn = now()->addDays(10);
        $checkOut = $checkIn->copy()->addDays(2);

        CalendarBlock::create([
            'property_id' => $room->property_id,
            'room_id' => $room->id,
            'type' => 'min_stay',
            'min_stay' => 5,
            'start_date' => $checkIn->copy()->subDay(),
            'end_date' => $checkOut->copy()->addDay(),
        ]);

        $this->assertSame(5, $this->engine->minimumStayForRange($room, $checkIn, $checkOut));
    }

    public function test_maximum_stay_calendar_block_caps_maximum(): void
    {
        $room = $this->makeRoom(100);
        $checkIn = now()->addDays(10);
        $checkOut = $checkIn->copy()->addDays(2);

        CalendarBlock::create([
            'property_id' => $room->property_id,
            'room_id' => $room->id,
            'type' => 'max_stay',
            'max_stay' => 3,
            'start_date' => $checkIn->copy()->subDay(),
            'end_date' => $checkOut->copy()->addDay(),
        ]);

        $this->assertSame(3, $this->engine->maximumStayForRange($room, $checkIn, $checkOut));
    }

    public function test_relative_competitor_rule_matches_average_competitor_rate(): void
    {
        $room = $this->makeRoom(100);
        $date = now()->addDays(10);

        CompetitorRate::create([
            'property_id' => $room->property_id,
            'room_id' => $room->id,
            'competitor' => 'Airbnb',
            'date' => $date->toDateString(),
            'rate' => 120,
            'source' => 'scrape',
            'captured_at' => now(),
        ]);
        CompetitorRate::create([
            'property_id' => $room->property_id,
            'room_id' => $room->id,
            'competitor' => 'Booking',
            'date' => $date->toDateString(),
            'rate' => 140,
            'source' => 'scrape',
            'captured_at' => now(),
        ]);

        PricingRule::create([
            'room_id' => $room->id,
            'name' => 'Match competitor',
            'rule_type' => 'competitor',
            'start_date' => $date->copy()->subDay(),
            'end_date' => $date->copy()->addDay(),
            'adjustment_type' => 'relative',
            'adjustment_value' => 0,
            'priority' => 5,
        ]);

        // Average competitor rate for the date: (120 + 140) / 2 = 130
        $this->assertSame(130.0, $this->engine->calculateRateForDate($room, $date));
    }

    public function test_recurring_rule_applies_same_window_every_year(): void
    {
        $room = $this->makeRoom(100);

        PricingRule::create([
            'room_id' => $room->id,
            'name' => 'Grand Prix',
            'rule_type' => 'event',
            'start_date' => '2026-07-02',
            'end_date' => '2026-07-04',
            'adjustment_type' => 'amount',
            'adjustment_value' => 50,
            'recurring' => true,
            'priority' => 1,
        ]);

        // Inside the window in 2026, 2027 and 2028 (even with a prior year's date).
        $this->assertSame(150.0, $this->engine->calculateRateForDate($room, Carbon::parse('2026-07-03')));
        $this->assertSame(150.0, $this->engine->calculateRateForDate($room, Carbon::parse('2027-07-02')));
        $this->assertSame(150.0, $this->engine->calculateRateForDate($room, Carbon::parse('2028-07-04')));

        // Outside the window the base rate applies.
        $this->assertSame(100.0, $this->engine->calculateRateForDate($room, Carbon::parse('2026-07-05')));
        $this->assertSame(100.0, $this->engine->calculateRateForDate($room, Carbon::parse('2026-01-15')));
    }

    public function test_non_recurring_rule_only_applies_in_its_year(): void
    {
        $room = $this->makeRoom(100);

        PricingRule::create([
            'room_id' => $room->id,
            'name' => 'One-off event',
            'rule_type' => 'event',
            'start_date' => '2026-07-02',
            'end_date' => '2026-07-04',
            'adjustment_type' => 'amount',
            'adjustment_value' => 50,
            'recurring' => false,
            'priority' => 1,
        ]);

        $this->assertSame(150.0, $this->engine->calculateRateForDate($room, Carbon::parse('2026-07-03')));
        $this->assertSame(100.0, $this->engine->calculateRateForDate($room, Carbon::parse('2027-07-03')));
    }

    public function test_event_rule_overrides_weekend_seasonal_rate(): void
    {
        $room = $this->makeRoom(550);

        PricingRule::create([
            'room_id' => $room->id,
            'name' => 'Weekend +75',
            'rule_type' => 'seasonal',
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'adjustment_type' => 'amount',
            'adjustment_value' => 75,
            'apply_weekends_only' => true,
            'recurring' => false,
            'priority' => 5,
        ]);

        PricingRule::create([
            'room_id' => $room->id,
            'name' => 'Christmas',
            'rule_type' => 'event',
            'start_date' => '2026-12-24',
            'end_date' => '2026-12-26',
            'adjustment_type' => 'amount',
            'adjustment_value' => 200,
            'recurring' => true,
            'priority' => 1,
        ]);

        // Christmas day 2026 (a Friday) is a weekend but the higher-priority
        // event rule wins, so the rate is 550 + 200 = 750, not 625.
        $this->assertSame(750.0, $this->engine->calculateRateForDate($room, Carbon::parse('2026-12-25')));

        // A non-holiday weekend stays at 550 + 75 = 625.
        $this->assertSame(625.0, $this->engine->calculateRateForDate($room, Carbon::parse('2026-11-07')));
    }

    public function test_recurring_rule_raises_minimum_stay(): void
    {
        $room = $this->makeRoom(100);
        $checkIn = Carbon::parse('2027-07-03');
        $checkOut = $checkIn->copy()->addDays(2);

        PricingRule::create([
            'room_id' => $room->id,
            'name' => 'Grand Prix min stay',
            'rule_type' => 'event',
            'start_date' => '2026-07-02',
            'end_date' => '2026-07-04',
            'adjustment_type' => 'amount',
            'adjustment_value' => 0,
            'minimum_stay' => 3,
            'recurring' => true,
            'priority' => 1,
        ]);

        $this->assertSame(3, $this->engine->minimumStayForRange($room, $checkIn, $checkOut));
    }

    public function test_length_of_stay_rule_applies_highest_qualifying_discount_tier(): void
    {
        $room = $this->makeRoom(100);

        foreach ([4 => 10, 7 => 25, 14 => 30, 28 => 35] as $minNights => $pct) {
            PricingRule::create([
                'property_id' => $room->property_id,
                'name' => "Long stay {$pct}% from {$minNights} nights",
                'rule_type' => 'length_of_stay',
                'adjustment_type' => 'percent',
                'adjustment_value' => -$pct,
                'minimum_stay' => $minNights,
                'recurring' => true,
                'priority' => 5,
            ]);
        }

        $quote = fn (int $nights): array => $this->engine->calculateForRange(
            $room,
            Carbon::parse('2026-06-01'),
            Carbon::parse('2026-06-01')->addDays($nights),
        );

        $this->assertSame(0.0, $quote(3)['discount_amount']);
        $this->assertSame(40.0, $quote(4)['discount_amount']);
        $this->assertSame(175.0, $quote(7)['discount_amount']);
        $this->assertSame(250.0, $quote(10)['discount_amount']);
        $this->assertSame(420.0, $quote(14)['discount_amount']);
        $this->assertSame(980.0, $quote(28)['discount_amount']);
    }

    public function test_length_of_stay_rule_does_not_alter_nightly_rates(): void
    {
        $room = $this->makeRoom(100);

        PricingRule::create([
            'property_id' => $room->property_id,
            'name' => 'Long stay 25% from 7 nights',
            'rule_type' => 'length_of_stay',
            'adjustment_type' => 'percent',
            'adjustment_value' => -25,
            'minimum_stay' => 7,
            'recurring' => true,
            'priority' => 5,
        ]);

        $this->assertSame(100.0, $this->engine->calculateRateForDate($room, Carbon::parse('2026-04-15')));

        $this->assertSame(2, $this->engine->minimumStayForRange(
            $room,
            Carbon::parse('2026-06-01'),
            Carbon::parse('2026-06-04'),
        ));
    }

    public function test_festive_period_raises_minimum_stay_to_three(): void
    {
        $room = $this->makeRoom(100);

        $this->assertSame(3, $this->engine->minimumStayForRange(
            $room,
            Carbon::parse('2027-12-29'),
            Carbon::parse('2027-12-31'),
        ));

        $this->assertSame(3, $this->engine->minimumStayForRange(
            $room,
            Carbon::parse('2027-12-31'),
            Carbon::parse('2028-01-01'),
        ));

        $this->assertSame(2, $this->engine->minimumStayForRange(
            $room,
            Carbon::parse('2027-12-12'),
            Carbon::parse('2027-12-14'),
        ));
    }

    public function test_holiday_weekend_uplift_applies_on_bank_holiday_weekends(): void
    {
        Setting::firstOrCreate(['key' => 'holiday_weekend_uplift_enabled'], ['value' => '1', 'group' => 'pricing', 'label' => 'Uplift enabled', 'cast' => 'boolean']);
        Setting::firstOrCreate(['key' => 'holiday_weekend_uplift'], ['value' => '5', 'group' => 'pricing', 'label' => 'Uplift', 'cast' => 'integer']);

        $room = $this->makeRoom(100);

        PricingRule::create([
            'property_id' => $room->property_id,
            'name' => 'Weekend +75',
            'rule_type' => 'seasonal',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'adjustment_type' => 'amount',
            'adjustment_value' => 75,
            'apply_weekends_only' => true,
            'priority' => 5,
        ]);

        // Saturday 23 May 2026 precedes the Spring bank holiday Monday (25 May).
        $bankHolidaySaturday = $this->engine->calculateRateForDate($room, Carbon::parse('2026-05-23'));
        $this->assertSame(183.75, $bankHolidaySaturday);

        // A normal Saturday is untouched by the uplift.
        $this->assertSame(175.0, $this->engine->calculateRateForDate($room, Carbon::parse('2026-05-16')));
    }

    public function test_holiday_weekend_uplift_skips_event_rules_and_explicit_rates(): void
    {
        Setting::firstOrCreate(['key' => 'holiday_weekend_uplift_enabled'], ['value' => '1', 'group' => 'pricing', 'label' => 'Uplift enabled', 'cast' => 'boolean']);
        Setting::firstOrCreate(['key' => 'holiday_weekend_uplift'], ['value' => '5', 'group' => 'pricing', 'label' => 'Uplift', 'cast' => 'integer']);

        $room = $this->makeRoom(550);

        PricingRule::create([
            'property_id' => $room->property_id,
            'name' => 'Weekend +75',
            'rule_type' => 'seasonal',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'adjustment_type' => 'amount',
            'adjustment_value' => 75,
            'apply_weekends_only' => true,
            'priority' => 5,
        ]);

        PricingRule::create([
            'property_id' => $room->property_id,
            'name' => 'Christmas',
            'rule_type' => 'event',
            'start_date' => '2026-12-24',
            'end_date' => '2026-12-26',
            'adjustment_type' => 'amount',
            'adjustment_value' => 200,
            'recurring' => true,
            'priority' => 1,
        ]);

        // Christmas Day 2026 (Friday) keeps its fixed event rate of 750.
        $this->assertSame(750.0, $this->engine->calculateRateForDate($room, Carbon::parse('2026-12-25')));

        // A festive-season Sunday outside the event window gets the uplift: 625 * 1.05.
        $this->assertSame(656.25, $this->engine->calculateRateForDate($room, Carbon::parse('2026-12-27')));

        // An explicit daily price block is never uplifted.
        CalendarBlock::create([
            'property_id' => $room->property_id,
            'room_id' => $room->id,
            'type' => 'daily_price',
            'value' => 250,
            'start_date' => '2026-05-23',
            'end_date' => '2026-05-23',
        ]);

        $this->assertSame(250.0, $this->engine->calculateRateForDate($room, Carbon::parse('2026-05-23')));
    }
}
