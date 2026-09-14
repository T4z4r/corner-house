<?php

namespace Database\Seeders;

use App\Models\PricingRule;
use App\Models\Property;
use App\Models\Room;
use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class PricingSeeder extends Seeder
{
    /**
     * Configure Corner House whole-house nightly pricing:
    * - Weekday base rate £550, Friday-Sunday £625
    * - Holiday weekend uplift is applied only on configured uplift days
     */
    public function run(): void
    {
        $property = Property::query()->where('status', 'active')->first();

        if (! $property) {
            return;
        }

        $rooms = Room::query()->where('property_id', $property->id)->get();

        // Base rate for every room = weekday whole-house rate.
        $rooms->each(fn (Room $room) => $room->update(['base_rate' => 550]));

        // Long-stay discounts applied to the whole stay, largest qualifying tier wins.
        foreach ([4 => 10, 7 => 25, 14 => 30, 28 => 35] as $minNights => $discountPct) {
            $this->upsertLongStayRule($property, $minNights, $discountPct);
        }

        $this->alignSettings();
    }

    private function upsertLongStayRule(Property $property, int $minNights, int $discountPct): void
    {
        PricingRule::updateOrCreate(
            [
                'property_id' => $property->id,
                'room_id' => null,
                'rule_type' => 'length_of_stay',
                'minimum_stay' => $minNights,
            ],
            [
                'name' => "Long-stay discount: {$discountPct}% off stays of {$minNights}+ nights",
                'rule_type' => 'length_of_stay',
                'start_date' => null,
                'end_date' => null,
                'priority' => 5,
                'adjustment_type' => 'percent',
                'adjustment_value' => -$discountPct,
                'minimum_stay' => $minNights,
                'max_stay' => null,
                'occupancy_threshold' => null,
                'days_before_checkin' => null,
                'apply_weekends_only' => false,
                'recurring' => true,
                'is_enabled' => true,
            ],
        );
    }

    private function upsertRule(Property $property, string $name, array $attributes): void
    {
        PricingRule::updateOrCreate(
            [
                'property_id' => $property->id,
                'room_id' => null,
                'name' => $name,
            ],
            [
                'rule_type' => $attributes['rule_type'],
                'start_date' => $attributes['start_date'],
                'end_date' => $attributes['end_date'],
                'priority' => 1,
                'adjustment_type' => $attributes['adjustment_type'],
                'adjustment_value' => $attributes['adjustment_value'],
                'minimum_stay' => null,
                'max_stay' => null,
                'occupancy_threshold' => null,
                'days_before_checkin' => null,
                'apply_weekends_only' => $attributes['apply_weekends_only'],
                'recurring' => $attributes['recurring'],
                'is_enabled' => true,
            ],
        );
    }

    private function alignSettings(): void
    {
        Setting::updateOrCreate(['key' => 'min_price_weekday'], [
            'group' => 'booking',
            'value' => '550',
            'label' => 'Minimum price - weekday (£/night)',
            'cast' => 'decimal:2',
        ]);
        Setting::updateOrCreate(['key' => 'min_price_weekend'], [
            'group' => 'booking',
            'value' => '625',
            'label' => 'Minimum price - weekend (£/night)',
            'cast' => 'decimal:2',
        ]);
        Setting::updateOrCreate(['key' => 'nightly_rate'], [
            'group' => 'website',
            'value' => '625',
            'label' => 'Whole-house rate (£/night)',
            'cast' => 'decimal:2',
        ]);

        $this->updateBookingRulesCopy();
    }

    private function updateBookingRulesCopy(): void
    {
        $rules = Setting::getValue('website_booking_rules', []);

        if (! is_array($rules)) {
            return;
        }

        foreach ($rules as &$group) {
            if (! is_array($group) || ($group['title'] ?? null) !== 'Pricing and payment') {
                continue;
            }

            foreach ($group['items'] as &$item) {
                if (is_string($item) && str_contains($item, 'low season')) {
                    $item = 'The nightly rate is &pound;550 Monday to Thursday and &pound;625 Friday to Sunday, rising to &pound;645 Friday to Sunday from April 2027, plus a &pound;50 cleaning fee and a refundable security deposit.';
                }
            }
            unset($item);
        }
        unset($group);

        Setting::updateOrCreate(['key' => 'website_booking_rules'], [
            'group' => 'website',
            'value' => json_encode($rules),
            'label' => 'Booking — booking rules',
            'cast' => 'json',
        ]);
    }
}
