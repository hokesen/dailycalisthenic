<?php

namespace App\Filament\Resources\SessionTemplates\RelationManagers;

use App\Filament\Forms\Components\SessionTemplateExercisePivotFields;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ExercisesRelationManager extends RelationManager
{
    protected static string $relationship = 'exercises';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components(
                SessionTemplateExercisePivotFields::make(fn () => $this->getOwnerRecord()->exercises()->count() + 1)
            );
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('order')
                    ->sortable()
                    ->badge()
                    ->color('primary')
                    ->state(fn ($record) => $record->pivot->order),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('duration_seconds')
                    ->label('Duration')
                    ->suffix('s')
                    ->sortable()
                    ->state(fn ($record) => $record->pivot->duration_seconds)
                    ->placeholder('-'),
                TextColumn::make('rest_after_seconds')
                    ->label('Rest After')
                    ->suffix('s')
                    ->sortable()
                    ->state(fn ($record) => $record->pivot->rest_after_seconds)
                    ->placeholder('-'),
                TextColumn::make('sets')
                    ->label('Sets')
                    ->sortable()
                    ->state(fn ($record) => $record->pivot->sets)
                    ->placeholder('-'),
                TextColumn::make('reps')
                    ->label('Reps')
                    ->sortable()
                    ->state(fn ($record) => $record->pivot->reps)
                    ->placeholder('-'),
                TextColumn::make('notes')
                    ->label('Notes')
                    ->limit(30)
                    ->toggleable()
                    ->state(fn ($record) => $record->pivot->notes)
                    ->placeholder('-'),
            ])
            ->filters([])
            ->headerActions([
                AttachAction::make()
                    ->preloadRecordSelect()
                    ->form(fn (AttachAction $action): array => array_merge(
                        [
                            $action->getRecordSelect()
                                ->searchable()
                                ->required(),
                        ],
                        SessionTemplateExercisePivotFields::make(fn () => $this->getOwnerRecord()->exercises()->count() + 1)
                    )),
            ])
            ->recordActions([
                EditAction::make()
                    ->form(fn (): array => SessionTemplateExercisePivotFields::make()),
                DetachAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make(),
                ]),
            ])
            ->defaultSort('order');
    }
}
