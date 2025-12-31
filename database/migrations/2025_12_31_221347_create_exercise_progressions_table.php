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
        Schema::create('exercise_progressions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exercise_id')->constrained()->onDelete('cascade');
            $table->foreignId('easier_exercise_id')->nullable()->constrained('exercises')->onDelete('set null');
            $table->foreignId('harder_exercise_id')->nullable()->constrained('exercises')->onDelete('set null');
            $table->integer('order')->default(0);
            $table->string('progression_path_name')->nullable();
            $table->timestamps();

            $table->index('exercise_id');
            $table->index('progression_path_name');
            $table->unique(['exercise_id', 'progression_path_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exercise_progressions');
    }
};
