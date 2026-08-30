<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('room_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('rule_type'); // seasonal | event | holiday | occupancy | last_minute | competitor | demand
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->unsignedInteger('priority')->default(100);
            $table->string('adjustment_type')->default('percent'); // percent | fixed
            $table->decimal('adjustment_value', 12, 2)->default(0); // percent or fixed amount
            $table->unsignedInteger('minimum_stay')->nullable();
            $table->decimal('occupancy_threshold', 5, 2)->nullable();
            $table->unsignedInteger('days_before_checkin')->nullable(); // for last-minute window
            $table->boolean('apply_weekends_only')->default(false);
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            $table->index(['rule_type', 'is_enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_rules');
    }
};
