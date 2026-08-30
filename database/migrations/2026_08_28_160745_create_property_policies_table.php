<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('title');
            $table->text('content')->nullable();
            $table->string('time')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            $table->index(['property_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_policies');
    }
};
