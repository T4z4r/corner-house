<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('communications', function (Blueprint $table) {
            $table->enum('channel', ['email', 'sms', 'whatsapp', 'beds24'])->default('email')->change();
        });
    }

    public function down(): void
    {
        Schema::table('communications', function (Blueprint $table) {
            $table->enum('channel', ['email', 'sms', 'whatsapp'])->default('email')->change();
        });
    }
};
