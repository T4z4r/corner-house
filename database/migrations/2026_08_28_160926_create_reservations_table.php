<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('property_id')->constrained();
            $table->foreignId('room_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('guest_id')->nullable()->constrained()->nullOnDelete();

            $table->date('check_in');
            $table->date('check_out');
            $table->unsignedInteger('guests_count')->default(1);

            $table->enum('status', [
                'pending',
                'hold',
                'confirmed',
                'checked_in',
                'checked_out',
                'cancelled',
                'no_show',
            ])->default('pending')->index();

            $table->string('source')->default('direct'); // direct | airbnb | booking.com | vrbo | manual
            $table->string('channel')->nullable();

            // Idempotency for external bookings
            $table->string('external_channel')->nullable();
            $table->string('external_booking_id')->nullable();
            $table->unique(['external_channel', 'external_booking_id'], 'reservations_ext_unique');

            $table->decimal('base_amount', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('fees_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);

            $table->enum('payment_status', ['unpaid', 'partial', 'paid', 'refunded'])->default('unpaid');

            $table->enum('sync_status', ['none', 'pending', 'synced', 'failed'])->default('none')->index();
            $table->json('sync_attempts')->nullable();

            $table->text('notes')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->json('cancellation_reason')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['property_id', 'check_in', 'check_out']);
            $table->index(['status', 'check_in']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
