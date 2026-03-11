<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->foreignId('training_program_enrollment_id')->nullable()->after('session_template_id')->constrained()->nullOnDelete();
            $table->string('program_day_key')->nullable()->after('training_program_enrollment_id');
        });
    }

    public function down(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->dropColumn('program_day_key');
            $table->dropConstrainedForeignId('training_program_enrollment_id');
        });
    }
};
