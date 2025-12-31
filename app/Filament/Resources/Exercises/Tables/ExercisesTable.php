<?php

namespace App\Filament\Resources\Exercises\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
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
                    ->color(fn (string $state): string => match ($state) {
                        'push' => 'danger',
                        'pull' => 'success',
                        'legs' => 'warning',
                        'core' => 'info',
                        'full_body' => 'primary',
                        'cardio' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => str($state)->title()->replace('_', ' '))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('difficulty_level')
                    ->label('Difficulty')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'beginner' => 'success',
                        'intermediate' => 'warning',
                        'advanced' => 'danger',
                        'expert' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => $state ? str($state)->title() : '-')
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
                    ->options([
                        'push' => 'Push',
                        'pull' => 'Pull',
                        'legs' => 'Legs',
                        'core' => 'Core',
                        'full_body' => 'Full Body',
                        'cardio' => 'Cardio',
                    ]),
                SelectFilter::make('difficulty_level')
                    ->options([
                        'beginner' => 'Beginner',
                        'intermediate' => 'Intermediate',
                        'advanced' => 'Advanced',
                        'expert' => 'Expert',
                    ]),
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
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name');
    }
}
