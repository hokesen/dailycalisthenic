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
            $table->decimal('weight_lbs', 8, 2)->nullable()->after('intensity');
            $table->unsignedInteger('reps_completed')->nullable()->after('weight_lbs');
            $table->unsignedInteger('sets_completed')->nullable()->after('reps_completed');
            $table->string('lift_category', 30)->nullable()->after('sets_completed');
            $table->boolean('is_personal_record')->default(false)->after('lift_category');

            $table->index(['lift_category', 'weight_lbs']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('session_exercises', function (Blueprint $table) {
            $table->dropIndex(['lift_category', 'weight_lbs']);
            $table->dropColumn([
                'weight_lbs',
                'reps_completed',
                'sets_completed',
                'lift_category',
                'is_personal_record',
            ]);
        });
    }
};
