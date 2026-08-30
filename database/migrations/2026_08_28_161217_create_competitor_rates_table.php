<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competitor_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_id')->nullable()->constrained()->nullOnDelete();
            $table->string('competitor');
            $table->date('date');
            $table->decimal('rate', 12, 2);
            $table->string('source')->nullable();
            $table->timestamp('captured_at');
            $table->timestamps();

            $table->index(['room_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competitor_rates');
    }
};
