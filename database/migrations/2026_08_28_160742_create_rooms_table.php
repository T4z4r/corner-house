<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->text('description')->nullable();
            $table->string('type')->nullable();
            $table->unsignedInteger('capacity')->default(1);
            $table->unsignedInteger('sleeps')->default(1);
            $table->unsignedInteger('bedrooms')->default(1);
            $table->unsignedInteger('bathrooms')->default(1);
            $table->boolean('is_private')->default(true);
            $table->enum('status', ['active', 'inactive', 'maintenance'])->default('active');
            $table->decimal('base_rate', 12, 2)->default(0);
            $table->unsignedSmallInteger('min_stay')->default(1);
            $table->unsignedSmallInteger('max_stay')->nullable();
            $table->timestamps();

            $table->index(['property_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
