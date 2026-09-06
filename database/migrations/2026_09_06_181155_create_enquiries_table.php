<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enquiries', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['booking', 'contact'])->default('booking');
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('guests')->nullable();
            $table->date('check_in')->nullable();
            $table->date('check_out')->nullable();
            $table->unsignedSmallInteger('nights')->nullable();
            $table->text('message')->nullable();
            $table->boolean('drinks_package')->default(false);
            $table->boolean('terms_accepted')->default(false);
            $table->enum('status', ['new', 'read'])->default('new')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enquiries');
    }
};