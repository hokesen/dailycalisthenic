<?php

namespace App\Filament\Exports;

use App\Models\ExerciseProgression;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class ExerciseProgressionExporter extends Exporter
{
    protected static ?string $model = ExerciseProgression::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id'),
            ExportColumn::make('exercise_id'),
            ExportColumn::make('easier_exercise_id'),
            ExportColumn::make('harder_exercise_id'),
            ExportColumn::make('order'),
            ExportColumn::make('progression_path_name'),
            ExportColumn::make('created_at'),
            ExportColumn::make('updated_at'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your exercise progression export has completed and '.Number::format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.Number::format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }
}
