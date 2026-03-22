<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            if (! $this->indexExists('journal_entries', 'journal_entries_user_id_index')) {
                $table->index('user_id', 'journal_entries_user_id_index');
            }
        });

        Schema::table('journal_entries', function (Blueprint $table) {
            if ($this->indexExists('journal_entries', 'journal_entries_user_id_entry_date_unique')) {
                $table->dropUnique('journal_entries_user_id_entry_date_unique');
            }
        });
    }

    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            if (! $this->indexExists('journal_entries', 'journal_entries_user_id_entry_date_unique')) {
                $table->unique(['user_id', 'entry_date']);
            }
        });

        Schema::table('journal_entries', function (Blueprint $table) {
            if ($this->indexExists('journal_entries', 'journal_entries_user_id_index')) {
                $table->dropIndex('journal_entries_user_id_index');
            }
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            $database = Schema::getConnection()->getDatabaseName();

            return DB::table('information_schema.statistics')
                ->where('table_schema', $database)
                ->where('table_name', $table)
                ->where('index_name', $index)
                ->exists();
        }

        if ($driver === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list('{$table}')");

            return collect($indexes)->contains(fn (object $row) => $row->name === $index);
        }

        return false;
    }
};
