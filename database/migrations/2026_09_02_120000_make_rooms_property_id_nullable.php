<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table): void {
            $table->dropForeign(['property_id']);
            $table->foreignId('property_id')->nullable()->change();
            $table->foreign('property_id')->references('id')->on('properties')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table): void {
            $table->dropForeign(['property_id']);
            $table->foreignId('property_id')->nullable(false)->change();
            $table->foreign('property_id')->references('id')->on('properties')->cascadeOnDelete();
        });
    }
};
