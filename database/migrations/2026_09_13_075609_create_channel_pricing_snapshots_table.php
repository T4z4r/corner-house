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
        Schema::create('channel_pricing_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_account_id')->constrained('channel_accounts')->cascadeOnDelete();
            $table->foreignId('room_id')->nullable()->constrained('rooms')->nullOnDelete();
            $table->string('external_room_id');
            $table->string('rate_code')->nullable();
            $table->date('date_from')->nullable();
            $table->date('date_to')->nullable();
            $table->longText('raw_data');
            $table->json('rows')->nullable();
            $table->unsignedInteger('open_days')->default(0);
            $table->unsignedInteger('closed_days')->default(0);
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->index(['channel_account_id', 'synced_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('channel_pricing_snapshots');
    }
};
