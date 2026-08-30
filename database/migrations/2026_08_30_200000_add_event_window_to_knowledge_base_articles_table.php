<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('knowledge_base_articles', function (Blueprint $table): void {
            $table->date('starts_at')->nullable()->after('priority');
            $table->date('ends_at')->nullable()->after('starts_at');
            $table->index(['category', 'starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::table('knowledge_base_articles', function (Blueprint $table): void {
            $table->dropIndex(['category', 'starts_at', 'ends_at']);
            $table->dropColumn(['starts_at', 'ends_at']);
        });
    }
};
