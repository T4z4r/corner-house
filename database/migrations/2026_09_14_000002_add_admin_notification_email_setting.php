<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $existing = Setting::getValue('booking_notify_email', 'tazarchriss@gmail.com');

        Setting::updateOrCreate(
            ['key' => 'admin_notification_email'],
            [
                'group' => 'notifications',
                'value' => $existing,
                'label' => 'Booking & system notifications recipient email',
                'cast' => 'string',
            ],
        );

        Setting::query()->where('key', 'booking_notify_email')->update([
            'label' => 'Booking & system notifications recipient email',
        ]);
    }

    public function down(): void
    {
        Setting::where('key', 'admin_notification_email')->delete();
    }
};
