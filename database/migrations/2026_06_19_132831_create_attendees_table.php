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
        Schema::create('attendees', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('event_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('status')->default('interested'); // interested | attending
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('reminded_3d_at')->nullable();  // idempotency: 3-day reminder
            $table->timestamp('reminded_24h_at')->nullable(); // idempotency: 24-hour reminder
            $table->timestamps();

            $table->unique(['event_id', 'email']);
            // Drives the reminder query: upcoming events that still need a reminder.
            $table->index(['reminded_3d_at', 'reminded_24h_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendees');
    }
};
