<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->boolean('smoking_allowed')->default(false)->after('currency');
            $table->boolean('children_allowed')->default(true)->after('smoking_allowed');
            $table->boolean('parties_allowed')->default(false)->after('children_allowed');
            $table->string('pets_allowed', 20)->default('no')->after('parties_allowed');
            $table->string('check_in_from', 10)->default('15:00')->after('pets_allowed');
            $table->string('check_in_until', 10)->default('18:00')->after('check_in_from');
            $table->string('check_out_from', 10)->default('08:00')->after('check_in_until');
            $table->string('check_out_until', 10)->default('11:00')->after('check_out_from');
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn([
                'smoking_allowed', 'children_allowed', 'parties_allowed',
                'pets_allowed', 'check_in_from', 'check_in_until',
                'check_out_from', 'check_out_until',
            ]);
        });
    }
};
