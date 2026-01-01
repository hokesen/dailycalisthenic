<?php

namespace App\Filament\Resources\SessionTemplates\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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
            ->components([
                TextInput::make('order')
                    ->required()
                    ->numeric()
                    ->default(fn () => $this->getOwnerRecord()->exercises()->count() + 1)
                    ->minValue(1)
                    ->helperText('Order in which this exercise appears in the template'),
                TextInput::make('duration_seconds')
                    ->label('Duration')
                    ->numeric()
                    ->suffix('seconds')
                    ->placeholder('60')
                    ->helperText('Duration for this specific exercise'),
                TextInput::make('rest_after_seconds')
                    ->label('Rest After')
                    ->numeric()
                    ->suffix('seconds')
                    ->placeholder('30')
                    ->helperText('Rest time after completing this exercise'),
                TextInput::make('sets')
                    ->numeric()
                    ->minValue(1)
                    ->placeholder('3')
                    ->helperText('Number of sets (optional)'),
                TextInput::make('reps')
                    ->numeric()
                    ->minValue(1)
                    ->placeholder('10')
                    ->helperText('Number of reps per set (optional)'),
                Textarea::make('notes')
                    ->rows(2)
                    ->placeholder('Any specific notes for this exercise in the template')
                    ->columnSpanFull(),
            ]);
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
            ->filters([
                //
            ])
            ->headerActions([
                AttachAction::make()
                    ->preloadRecordSelect()
                    ->form(fn (AttachAction $action): array => [
                        $action->getRecordSelect()
                            ->searchable()
                            ->required(),
                        TextInput::make('order')
                            ->required()
                            ->numeric()
                            ->default(fn () => $this->getOwnerRecord()->exercises()->count() + 1)
                            ->minValue(1),
                        TextInput::make('duration_seconds')
                            ->numeric()
                            ->suffix('seconds'),
                        TextInput::make('rest_after_seconds')
                            ->numeric()
                            ->suffix('seconds'),
                        TextInput::make('sets')
                            ->numeric()
                            ->minValue(1),
                        TextInput::make('reps')
                            ->numeric()
                            ->minValue(1),
                        Textarea::make('notes')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ])
            ->recordActions([
                EditAction::make()
                    ->form(fn (): array => [
                        TextInput::make('order')
                            ->required()
                            ->numeric()
                            ->minValue(1),
                        TextInput::make('duration_seconds')
                            ->numeric()
                            ->suffix('seconds'),
                        TextInput::make('rest_after_seconds')
                            ->numeric()
                            ->suffix('seconds'),
                        TextInput::make('sets')
                            ->numeric()
                            ->minValue(1),
                        TextInput::make('reps')
                            ->numeric()
                            ->minValue(1),
                        Textarea::make('notes')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
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
