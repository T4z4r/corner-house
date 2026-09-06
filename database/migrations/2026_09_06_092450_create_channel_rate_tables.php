<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channel_rate_maps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_account_id')->constrained()->cascadeOnDelete();
            $table->string('external_property_id', 100);
            $table->string('hotel_id', 100)->nullable();
            $table->string('hotel_name', 255)->nullable();
            $table->longText('raw_xml')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['channel_account_id', 'external_property_id']);
        });

        Schema::create('channel_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_rate_map_id')->constrained()->cascadeOnDelete();
            $table->string('external_room_id', 100);
            $table->string('room_name', 255)->nullable();
            $table->string('external_rate_id', 100);
            $table->string('rate_name', 255);
            $table->string('policy', 100)->nullable();
            $table->string('policy_id', 100)->nullable();
            $table->unsignedInteger('max_persons')->nullable();
            $table->unsignedInteger('fixed_occupancy')->nullable();
            $table->boolean('is_child_rate')->default(false);
            $table->string('parent_rate_id', 100)->nullable();
            $table->boolean('follows_price')->nullable();
            $table->decimal('percentage', 6, 2)->nullable();
            $table->string('pricing_type', 20)->nullable();
            $table->string('meal_plan_code', 20)->nullable();
            $table->json('occupancy')->nullable();
            $table->json('policies')->nullable();
            $table->timestamps();

            $table->index(['external_room_id', 'external_rate_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channel_rates');
        Schema::dropIfExists('channel_rate_maps');
    }
};
