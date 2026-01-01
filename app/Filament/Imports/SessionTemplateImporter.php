<?php

namespace App\Filament\Imports;

use App\Models\Exercise;
use App\Models\SessionTemplate;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class SessionTemplateImporter extends Importer
{
    protected static ?string $model = SessionTemplate::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('user_id')
                ->numeric(),
            ImportColumn::make('name')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('description'),
            ImportColumn::make('notes'),
            ImportColumn::make('estimated_duration_minutes')
                ->numeric(),
            ImportColumn::make('default_rest_seconds')
                ->requiredMapping()
                ->numeric()
                ->rules(['required']),
            ImportColumn::make('exercises')
                ->castStateUsing(function (?string $state): ?array {
                    if (blank($state)) {
                        return null;
                    }

                    $decoded = json_decode($state, true);

                    if (json_last_error() !== JSON_ERROR_NONE) {
                        return null;
                    }

                    if (! is_array($decoded)) {
                        return null;
                    }

                    return $decoded;
                })
                ->fillRecordUsing(function () {
                    // Don't fill this into the model - it's handled in afterSave()
                })
                ->rules(['nullable']),
        ];
    }

    public function resolveRecord(): SessionTemplate
    {
        return new SessionTemplate;
    }

    protected function afterSave(): void
    {
        if (! isset($this->data['exercises']) || empty($this->data['exercises'])) {
            return;
        }

        try {
            $exercisesData = $this->data['exercises'];

            if (! is_array($exercisesData)) {
                return;
            }

            $pivotData = [];

            foreach ($exercisesData as $exerciseData) {
                if (! is_array($exerciseData) || empty($exerciseData['exercise_id'])) {
                    continue;
                }

                $exercise = Exercise::find($exerciseData['exercise_id']);

                if (! $exercise) {
                    continue;
                }

                $pivotData[$exercise->id] = [
                    'order' => $exerciseData['order'] ?? 0,
                    'duration_seconds' => $exerciseData['duration_seconds'] ?? null,
                    'rest_after_seconds' => $exerciseData['rest_after_seconds'] ?? null,
                    'sets' => $exerciseData['sets'] ?? null,
                    'reps' => $exerciseData['reps'] ?? null,
                    'notes' => $exerciseData['notes'] ?? null,
                ];
            }

            if (! empty($pivotData)) {
                $this->record->exercises()->sync($pivotData);
            }
        } catch (\Throwable $e) {
            logger()->error('Failed to import session template exercises', [
                'session_template_id' => $this->record->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your session template import has completed and '.Number::format($import->successful_rows).' '.str('row')->plural($import->successful_rows).' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.Number::format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to import.';
        }

        return $body;
    }
}
