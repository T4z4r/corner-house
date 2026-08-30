<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communication_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('event');
            $table->enum('channel', ['email', 'sms', 'whatsapp'])->default('email');
            $table->string('subject')->nullable();
            $table->text('body');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('communications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guest_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reservation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('communication_template_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('channel', ['email', 'sms', 'whatsapp'])->default('email');
            $table->string('recipient');
            $table->string('subject')->nullable();
            $table->text('body');
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending')->index();
            $table->string('provider_message_id')->nullable();
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communications');
        Schema::dropIfExists('communication_templates');
    }
};
