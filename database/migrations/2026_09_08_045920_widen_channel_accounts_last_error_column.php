<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A Beds24 sync that ends with errors is marked 'partial'. The original
     * status enum was ('active','inactive','error') so writing 'partial'
     * triggered a MySQL data-truncation warning, and a synchronise() that hit
     * that warning re-stored the failing UPDATE's SQL into last_error, which
     * then overflowed the VARCHAR(255) column and aborted the whole update.
     *
     * Fix both: widen last_error to text and allow the 'partial' status.
     */
    public function up(): void
    {
        Schema::table('channel_accounts', function (Blueprint $table) {
            $table->text('last_error')->nullable()->change();

            $table->enum('status', ['active', 'inactive', 'error', 'partial'])
                ->default('inactive')
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('channel_accounts', function (Blueprint $table) {
            $table->string('last_error')->nullable()->change();

            $table->enum('status', ['active', 'inactive', 'error'])
                ->default('inactive')
                ->change();
        });
    }
};
