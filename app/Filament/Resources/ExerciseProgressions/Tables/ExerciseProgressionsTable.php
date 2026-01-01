<?php

namespace App\Filament\Resources\ExerciseProgressions\Tables;

use App\Filament\Exports\ExerciseProgressionExporter;
use App\Filament\Imports\ExerciseProgressionImporter;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\ImportAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ExerciseProgressionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('exercise.name')
                    ->searchable(),
                TextColumn::make('easierExercise.name')
                    ->searchable(),
                TextColumn::make('harderExercise.name')
                    ->searchable(),
                TextColumn::make('order')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('progression_path_name')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->headerActions([
                ExportAction::make()
                    ->exporter(ExerciseProgressionExporter::class),
                ImportAction::make()
                    ->importer(ExerciseProgressionImporter::class),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->exporter(ExerciseProgressionExporter::class),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
