<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Setting::updateOrCreate(
            ['key' => 'booking_notify_email'],
            [
                'group' => 'notifications',
                'value' => 'tazarchriss@gmail.com',
                'label' => 'New booking notification email',
                'cast' => 'string',
            ],
        );
    }

    public function down(): void
    {
        Setting::where('key', 'booking_notify_email')->delete();
    }
};