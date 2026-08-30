<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->unsignedInteger('capacity')->nullable()->default(1)->change();
            $table->unsignedInteger('bedrooms')->nullable()->default(0)->change();
            $table->unsignedInteger('bathrooms')->nullable()->default(0)->change();
            $table->string('country', 2)->nullable()->default('GB')->change();
        });

        Schema::table('rooms', function (Blueprint $table) {
            $table->unsignedInteger('capacity')->nullable()->default(1)->change();
            $table->unsignedInteger('sleeps')->nullable()->default(1)->change();
            $table->unsignedInteger('bedrooms')->nullable()->default(1)->change();
            $table->unsignedInteger('bathrooms')->nullable()->default(1)->change();
            $table->decimal('base_rate', 12, 2)->nullable()->default(0)->change();
            $table->unsignedSmallInteger('min_stay')->nullable()->default(1)->change();
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->unsignedInteger('capacity')->default(1)->nullable(false)->change();
            $table->unsignedInteger('bedrooms')->default(0)->nullable(false)->change();
            $table->unsignedInteger('bathrooms')->default(0)->nullable(false)->change();
            $table->string('country', 2)->default('GB')->nullable(false)->change();
        });

        Schema::table('rooms', function (Blueprint $table) {
            $table->unsignedInteger('capacity')->default(1)->nullable(false)->change();
            $table->unsignedInteger('sleeps')->default(1)->nullable(false)->change();
            $table->unsignedInteger('bedrooms')->default(1)->nullable(false)->change();
            $table->unsignedInteger('bathrooms')->default(1)->nullable(false)->change();
            $table->decimal('base_rate', 12, 2)->default(0)->nullable(false)->change();
            $table->unsignedSmallInteger('min_stay')->default(1)->nullable(false)->change();
        });
    }
};
