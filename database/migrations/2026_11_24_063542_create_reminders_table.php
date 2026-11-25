<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->timestamp('remind_at');
            $table->json('channels');
            $table->enum('status', ['pending', 'processing', 'sent', 'failed', 'cancelled', 'skipped'])->default('pending');
            $table->boolean('is_enabled')->default(true);
            $table->timestamp('snoozed_until')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->text('last_error')->nullable();
            $table->string('dedupe_key', 128)->unique();
            $table->timestamps();
            $table->index(['status', 'is_enabled', 'remind_at']);
            $table->index(['status', 'is_enabled', 'snoozed_until']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reminders');
    }
};
