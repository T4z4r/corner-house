<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table): void {
            $table->boolean('is_primary')->default(false)->after('is_private');
            $table->index(['is_primary', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table): void {
            $table->dropIndex(['is_primary', 'status']);
            $table->dropColumn('is_primary');
        });
    }
};