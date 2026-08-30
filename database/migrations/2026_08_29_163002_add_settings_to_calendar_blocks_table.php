<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calendar_blocks', function (Blueprint $table) {
            $table->decimal('value', 10, 2)->nullable()->after('notes');
            $table->integer('min_stay')->nullable()->after('value');
            $table->integer('max_stay')->nullable()->after('min_stay');
            $table->boolean('is_active')->default(true)->after('max_stay');
        });
    }

    public function down(): void
    {
        Schema::table('calendar_blocks', function (Blueprint $table) {
            $table->dropColumn(['value', 'min_stay', 'max_stay', 'is_active']);
        });
    }
};
