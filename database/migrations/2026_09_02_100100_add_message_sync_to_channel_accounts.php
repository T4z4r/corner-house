<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('channel_accounts', function (Blueprint $table) {
            $table->timestamp('last_message_synced_at')->nullable()->after('last_synced_at');
            $table->string('last_message_sync_status')->nullable()->after('last_message_synced_at');
        });
    }

    public function down(): void
    {
        Schema::table('channel_accounts', function (Blueprint $table) {
            $table->dropColumn(['last_message_synced_at', 'last_message_sync_status']);
        });
    }
};
