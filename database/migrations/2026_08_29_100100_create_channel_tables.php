<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channel_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('provider');
            $table->string('name');
            $table->enum('status', ['active', 'inactive', 'error'])->default('inactive')->index();
            $table->text('credentials')->nullable();
            $table->json('settings')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->string('last_error')->nullable();
            $table->timestamps();
        });

        Schema::create('channel_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider', 50);
            $table->string('external_property_id', 100)->nullable();
            $table->string('external_room_id', 100)->nullable();
            $table->string('external_listing_id', 100)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['provider', 'external_room_id']);
        });

        Schema::create('channel_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel', 50);
            $table->string('operation', 100);
            $table->json('request')->nullable();
            $table->json('response')->nullable();
            $table->enum('status', ['pending', 'success', 'failed'])->default('pending')->index();
            $table->text('error_message')->nullable();
            $table->string('external_id')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['channel', 'status', 'created_at']);
        });

        Schema::create('channel_webhooks', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 50);
            $table->string('event_type', 100)->nullable();
            $table->string('external_id', 100)->nullable()->index();
            $table->json('payload');
            $table->enum('status', ['received', 'processed', 'failed', 'ignored'])->default('received')->index();
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channel_webhooks');
        Schema::dropIfExists('channel_sync_logs');
        Schema::dropIfExists('channel_mappings');
        Schema::dropIfExists('channel_accounts');
    }
};
