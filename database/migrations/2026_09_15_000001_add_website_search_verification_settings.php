<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Setting::updateOrCreate(
            ['key' => 'website_google_site_verification'],
            ['group' => 'website', 'value' => '', 'label' => 'Google Search Console verification code', 'cast' => 'string'],
        );

        Setting::updateOrCreate(
            ['key' => 'website_bing_site_verification'],
            ['group' => 'website', 'value' => '', 'label' => 'Bing Webmaster Tools verification code', 'cast' => 'string'],
        );
    }

    public function down(): void
    {
        Setting::whereIn('key', ['website_google_site_verification', 'website_bing_site_verification'])->delete();
    }
};
