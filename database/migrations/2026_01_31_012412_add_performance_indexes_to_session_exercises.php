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
        Schema::table('session_exercises', function (Blueprint $table) {
            // Add index for completed_at to optimize gantt chart queries
            $table->index('completed_at');
            // Add composite index for exercise_id and completed_at for streak calculations
            $table->index(['exercise_id', 'completed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('session_exercises', function (Blueprint $table) {
            $table->dropIndex(['completed_at']);
            $table->dropIndex(['exercise_id', 'completed_at']);
        });
    }
};
