<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pricing_rules', function (Blueprint $table): void {
            $table->boolean('generated_by_ai')->default(false)->after('is_enabled');
            $table->string('ai_generation_key')->nullable()->after('generated_by_ai');
            $table->json('generation_metadata')->nullable()->after('ai_generation_key');
            $table->timestamp('generated_at')->nullable()->after('generation_metadata');
            $table->index(['property_id', 'ai_generation_key']);
        });
    }

    public function down(): void
    {
        Schema::table('pricing_rules', function (Blueprint $table): void {
            $table->dropIndex(['property_id', 'ai_generation_key']);
            $table->dropColumn(['generated_by_ai', 'ai_generation_key', 'generation_metadata', 'generated_at']);
        });
    }
};
