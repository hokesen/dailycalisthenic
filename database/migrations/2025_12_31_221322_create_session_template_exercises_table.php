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
        Schema::create('session_template_exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_template_id')->constrained()->onDelete('cascade');
            $table->foreignId('exercise_id')->constrained()->onDelete('cascade');
            $table->integer('order')->default(0);
            $table->integer('duration_seconds')->nullable();
            $table->integer('rest_after_seconds')->nullable();
            $table->integer('sets')->default(1);
            $table->integer('reps')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('session_template_id');
            $table->index('exercise_id');
            $table->unique(['session_template_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('session_template_exercises');
    }
};
