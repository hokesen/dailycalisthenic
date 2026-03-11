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
        Schema::create('meditation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('session_id')->nullable()->constrained('sessions')->nullOnDelete();
            $table->unsignedInteger('duration_seconds');
            $table->string('technique', 50)->nullable();
            $table->unsignedInteger('breath_cycles_completed')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('practiced_at');
            $table->timestamps();

            $table->index(['user_id', 'practiced_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meditation_logs');
    }
};
