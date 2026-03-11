<?php

use App\Enums\PracticeBlockCompletionMode;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('practice_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_template_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exercise_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(1);
            $table->string('title');
            $table->string('completion_mode')->default(PracticeBlockCompletionMode::Timed->value);
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->unsignedInteger('rest_after_seconds')->default(0);
            $table->unsignedInteger('repeats')->default(1);
            $table->string('distance_label')->nullable();
            $table->string('target_cue')->nullable();
            $table->text('setup_text')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['session_template_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('practice_blocks');
    }
};
