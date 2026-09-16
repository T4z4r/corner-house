<?php

App\Models\Setting::whereIn('key', ['website_booking_rules', 'website_blocked_dates', 'min_stay_nights', 'min_stay_bank_holiday_nights'])->get()->each(function ($s) {
    echo "=== ".$s->key." ===\n";
    echo is_string($s->value) ? $s->value : json_encode($s->value, JSON_UNESCAPED_UNICODE);
    echo "\n";
});