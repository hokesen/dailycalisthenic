<?php

namespace App\Filament\Imports;

use App\Models\ExerciseProgression;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class ExerciseProgressionImporter extends Importer
{
    protected static ?string $model = ExerciseProgression::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('exercise_id')
                ->requiredMapping()
                ->numeric()
                ->rules(['required']),
            ImportColumn::make('easier_exercise_id')
                ->numeric(),
            ImportColumn::make('harder_exercise_id')
                ->numeric(),
            ImportColumn::make('order')
                ->requiredMapping()
                ->numeric()
                ->rules(['required']),
            ImportColumn::make('progression_path_name'),
        ];
    }

    public function resolveRecord(): ExerciseProgression
    {
        return ExerciseProgression::firstOrNew([
            'exercise_id' => $this->data['exercise_id'],
            'order' => $this->data['order'],
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your exercise progression import has completed and '.Number::format($import->successful_rows).' '.str('row')->plural($import->successful_rows).' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.Number::format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to import.';
        }

        return $body;
    }
}
