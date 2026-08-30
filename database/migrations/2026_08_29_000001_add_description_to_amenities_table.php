<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('amenities', function (Blueprint $table): void {
            $table->text('description')->nullable()->after('icon');
        });
    }

    public function down(): void
    {
        Schema::table('amenities', function (Blueprint $table): void {
            $table->dropColumn('description');
        });
    }
};
