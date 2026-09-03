<?php

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';

$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PricingOverride;
use App\Models\PricingRule;
use App\Models\Property;
use App\Models\Room;

echo "Property:\n";
foreach (Property::all() as $r) {
    echo "  {$r->id}: {$r->name}\n";
}

echo "\nRooms:\n";
foreach (Room::all() as $r) {
    echo "  {$r->id}: {$r->name} base_rate={$r->base_rate} min_stay={$r->min_stay} max_stay={$r->max_stay}\n";
}

echo "\nPricing rules:\n";
foreach (PricingRule::with('property', 'room')->get() as $r) {
    $wo = var_export($r->apply_weekends_only, true);
    $en = var_export($r->is_enabled, true);
    $rid = var_export($r->room_id, true);
    $pid = var_export($r->property_id, true);
    echo "  [#{$r->id}] {$r->name} | type={$r->rule_type} | adj={$r->adjustment_type}:{$r->adjustment_value} | weekend_only={$wo} | dates={$r->start_date}-{$r->end_date} | min_stay={$r->minimum_stay} | enabled={$en} | room_id={$rid} | prop_id={$pid}\n";
}

echo "\nPricing overrides:\n";
foreach (PricingOverride::with('room')->get() as $r) {
    $en = var_export($r->is_enabled, true);
    echo "  [#{$r->id}] room={$r->room_id} rate={$r->rate} dates={$r->start_date}-{$r->end_date} min_stay={$r->minimum_stay} enabled={$en} notes={$r->notes}\n";
}
