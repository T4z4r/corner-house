<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('places_of_interests', function (Blueprint $table) {
            $table->string('sub_category')->nullable()->after('category');
            $table->string('hours')->nullable()->after('phone');
            $table->boolean('is_local')->default(false)->after('distance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('places_of_interests', function (Blueprint $table) {
            $table->dropColumn(['sub_category', 'hours', 'is_local']);
        });
    }
};
