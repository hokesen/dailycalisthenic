<?php

use App\Enums\TrainingDiscipline;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exercises', function (Blueprint $table) {
            $table->string('discipline')->default(TrainingDiscipline::General->value)->after('category');
            $table->string('media_url')->nullable()->after('default_duration_seconds');
            $table->text('setup_text')->nullable()->after('instructions');
            $table->text('field_layout_notes')->nullable()->after('setup_text');

            $table->index('discipline');
        });

        Schema::table('session_templates', function (Blueprint $table) {
            $table->string('discipline')->default(TrainingDiscipline::General->value)->after('notes');

            $table->index('discipline');
        });

        DB::table('exercises')->update(['discipline' => TrainingDiscipline::General->value]);
        DB::table('session_templates')->update(['discipline' => TrainingDiscipline::General->value]);
    }

    public function down(): void
    {
        Schema::table('session_templates', function (Blueprint $table) {
            $table->dropIndex(['discipline']);
            $table->dropColumn('discipline');
        });

        Schema::table('exercises', function (Blueprint $table) {
            $table->dropIndex(['discipline']);
            $table->dropColumn([
                'discipline',
                'media_url',
                'setup_text',
                'field_layout_notes',
            ]);
        });
    }
};
