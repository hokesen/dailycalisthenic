<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_program_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('program_slug');
            $table->date('starts_on');
            $table->string('team_practice_band')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('completed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
            $table->index('program_slug');
        });

        Schema::create('training_program_day_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_program_enrollment_id')->constrained()->cascadeOnDelete();
            $table->string('program_day_key');
            $table->date('scheduled_for');
            $table->date('actual_date')->nullable();
            $table->string('status')->default('pending');
            $table->foreignId('session_id')->nullable()->constrained()->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique([
                'training_program_enrollment_id',
                'program_day_key',
                'scheduled_for',
            ], 'training_program_day_logs_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_program_day_logs');
        Schema::dropIfExists('training_program_enrollments');
    }
};
