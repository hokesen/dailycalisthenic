<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('assessment_slug');
            $table->date('recorded_on');
            $table->unsignedInteger('primary_result_seconds')->nullable();
            $table->json('results')->nullable();
            $table->json('split_results')->nullable();
            $table->string('derived_status')->nullable();
            $table->string('summary_label')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'assessment_slug', 'recorded_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_results');
    }
};
