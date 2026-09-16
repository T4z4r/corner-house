<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Link a booking enquiry to the room it references and the 48-hour hold
     * created on submission, so the team can review the request and convert it
     * to a reservation without losing the hold that guards the dates.
     */
    public function up(): void
    {
        Schema::table('enquiries', function (Blueprint $table) {
            $table->foreignId('room_id')->nullable()->after('email')->constrained()->nullOnDelete();
            $table->foreignId('booking_hold_id')->nullable()->after('room_id')->constrained('booking_holds')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('enquiries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('room_id');
            $table->dropConstrainedForeignId('booking_hold_id');
        });
    }
};