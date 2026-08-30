<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('revenue_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
            $table->date('snapshot_date');
            $table->decimal('revenue', 12, 2)->default(0);
            $table->decimal('occupancy_pct', 5, 2)->default(0);
            $table->decimal('adr', 12, 2)->default(0);
            $table->decimal('revpar', 12, 2)->default(0);
            $table->unsignedInteger('bookings_count')->default(0);
            $table->unsignedInteger('cancellations_count')->default(0);
            $table->unsignedInteger('direct_bookings')->default(0);
            $table->unsignedInteger('ota_bookings')->default(0);
            $table->timestamps();

            $table->unique(['property_id', 'snapshot_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revenue_snapshots');
    }
};
