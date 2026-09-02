<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $settings = [
            ['beds24_sync_bookings_enabled', '1', 'boolean', 'Enable booking sync'],
            ['beds24_sync_bookings_frequency', 'every_five_minutes', 'string', 'Booking sync frequency'],
            ['beds24_sync_messages_enabled', '1', 'boolean', 'Enable message sync'],
            ['beds24_sync_messages_frequency', 'every_five_minutes', 'string', 'Message sync frequency'],
            ['beds24_push_rates_enabled', '1', 'boolean', 'Enable rate push'],
            ['beds24_push_rates_frequency', 'hourly', 'string', 'Rate push frequency'],
        ];

        foreach ($settings as [$key, $value, $cast, $label]) {
            Setting::updateOrCreate(
                ['key' => "schedule_{$key}"],
                ['group' => 'schedule', 'value' => $value, 'cast' => $cast, 'label' => $label],
            );
        }
    }

    public function down(): void
    {
        Setting::query()->where('group', 'schedule')->delete();
    }
};
