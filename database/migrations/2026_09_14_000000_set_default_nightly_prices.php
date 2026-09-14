<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Setting::updateOrCreate(
            ['key' => 'min_price_weekday'],
            [
                'group' => 'booking',
                'value' => '550',
                'label' => 'Minimum price - weekday (£/night)',
                'cast' => 'decimal:2',
            ],
        );

        Setting::updateOrCreate(
            ['key' => 'min_price_weekend'],
            [
                'group' => 'booking',
                'value' => '625',
                'label' => 'Minimum price - weekend (£/night)',
                'cast' => 'decimal:2',
            ],
        );
    }

    public function down(): void
    {
        Setting::updateOrCreate(
            ['key' => 'min_price_weekday'],
            ['value' => '450'],
        );

        Setting::updateOrCreate(
            ['key' => 'min_price_weekend'],
            ['value' => '600'],
        );
    }
};
