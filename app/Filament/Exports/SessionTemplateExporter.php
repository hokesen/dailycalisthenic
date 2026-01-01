<?php

namespace App\Filament\Exports;

use App\Models\SessionTemplate;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class SessionTemplateExporter extends Exporter
{
    protected static ?string $model = SessionTemplate::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id'),
            ExportColumn::make('user_id'),
            ExportColumn::make('name'),
            ExportColumn::make('description'),
            ExportColumn::make('notes'),
            ExportColumn::make('estimated_duration_minutes'),
            ExportColumn::make('default_rest_seconds'),
            ExportColumn::make('exercises')
                ->state(function (SessionTemplate $record): string {
                    $exercises = $record->exercises->map(function ($exercise) {
                        return [
                            'exercise_id' => $exercise->id,
                            'exercise_name' => $exercise->name,
                            'order' => $exercise->pivot->order,
                            'duration_seconds' => $exercise->pivot->duration_seconds,
                            'rest_after_seconds' => $exercise->pivot->rest_after_seconds,
                            'sets' => $exercise->pivot->sets,
                            'reps' => $exercise->pivot->reps,
                            'notes' => $exercise->pivot->notes,
                        ];
                    })->toArray();

                    return json_encode($exercises);
                }),
            ExportColumn::make('created_at'),
            ExportColumn::make('updated_at'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your session template export has completed and '.Number::format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.Number::format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }
}
