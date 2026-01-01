<?php

namespace App\Filament\Imports;

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
        ];
    }

    public function resolveRecord(): SessionTemplate
    {
        return SessionTemplate::firstOrNew([
            'name' => $this->data['name'],
        ]);
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
