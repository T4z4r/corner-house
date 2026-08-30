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
        Schema::table('knowledge_base_articles', function (Blueprint $table) {
            $table->string('source', 50)->default('manual')->after('status');
            $table->boolean('show_on_website')->default(true)->index()->after('source');
            $table->foreignId('source_message_id')
                ->nullable()
                ->unique()
                ->after('show_on_website')
                ->constrained('ai_messages')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('knowledge_base_articles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('source_message_id');
            $table->dropColumn(['source', 'show_on_website']);
        });
    }
};
