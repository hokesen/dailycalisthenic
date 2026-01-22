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
        Schema::table('session_template_exercises', function (Blueprint $table) {
            $table->string('tempo')->nullable()->after('notes');
            $table->string('intensity')->nullable()->after('tempo');
        });

        // Also add to session_exercises for tracking actual practice
        Schema::table('session_exercises', function (Blueprint $table) {
            $table->string('tempo')->nullable()->after('notes');
            $table->string('intensity')->nullable()->after('tempo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('session_template_exercises', function (Blueprint $table) {
            $table->dropColumn(['tempo', 'intensity']);
        });

        Schema::table('session_exercises', function (Blueprint $table) {
            $table->dropColumn(['tempo', 'intensity']);
        });
    }
};
