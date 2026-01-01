<?php

namespace App\Filament\Resources\Exercises\Tables;

use App\Enums\ExerciseCategory;
use App\Enums\ExerciseDifficulty;
use App\Filament\Exports\ExerciseExporter;
use App\Filament\Imports\ExerciseImporter;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\ImportAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ExercisesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('user.name')
                    ->label('Owner')
                    ->badge()
                    ->color('gray')
                    ->default('System')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                IconColumn::make('user_id')
                    ->label('Type')
                    ->boolean()
                    ->trueIcon('heroicon-o-users')
                    ->falseIcon('heroicon-o-globe-alt')
                    ->trueColor('gray')
                    ->falseColor('primary')
                    ->state(fn ($record) => $record->user_id !== null)
                    ->tooltip(fn ($record) => $record->user_id ? 'User Exercise' : 'System Exercise'),
                TextColumn::make('category')
                    ->badge()
                    ->color(fn (?string $state): string => $state ? ExerciseCategory::from($state)->color() : 'gray')
                    ->formatStateUsing(fn (?string $state): string => $state ? ExerciseCategory::from($state)->label() : '-')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('difficulty_level')
                    ->label('Difficulty')
                    ->badge()
                    ->color(fn (?string $state): string => $state ? ExerciseDifficulty::from($state)->color() : 'gray')
                    ->formatStateUsing(fn (?string $state): string => $state ? ExerciseDifficulty::from($state)->label() : '-')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('default_duration_seconds')
                    ->label('Duration')
                    ->suffix('s')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
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
                SelectFilter::make('category')
                    ->options(ExerciseCategory::options()),
                SelectFilter::make('difficulty_level')
                    ->options(ExerciseDifficulty::options()),
                SelectFilter::make('type')
                    ->label('Exercise Type')
                    ->options([
                        'system' => 'System Exercises',
                        'user' => 'User Exercises',
                    ])
                    ->query(function ($query, $state) {
                        if ($state['value'] === 'system') {
                            return $query->whereNull('user_id');
                        }
                        if ($state['value'] === 'user') {
                            return $query->whereNotNull('user_id');
                        }

                        return $query;
                    }),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->headerActions([
                ExportAction::make()
                    ->exporter(ExerciseExporter::class),
                ImportAction::make()
                    ->importer(ExerciseImporter::class),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->exporter(ExerciseExporter::class),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name');
    }
}
