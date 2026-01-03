<?php

namespace App\Filament\Resources\Sessions\Tables;

use App\Enums\SessionStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SessionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Session Name')
                    ->searchable()
                    ->weight('bold')
                    ->default(fn ($record) => $record->template?->name ?? 'Custom Session'),
                TextColumn::make('template.name')
                    ->label('Template')
                    ->badge()
                    ->color('gray')
                    ->default('Custom')
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (SessionStatus $state): string => match ($state) {
                        SessionStatus::Planned => 'gray',
                        SessionStatus::InProgress => 'warning',
                        SessionStatus::Completed => 'success',
                        SessionStatus::Skipped => 'danger',
                        SessionStatus::Forgiven => 'info',
                    })
                    ->formatStateUsing(fn (SessionStatus $state): string => $state->label())
                    ->searchable()
                    ->sortable(),
                TextColumn::make('completed_at')
                    ->label('Completed')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->default('-')
                    ->toggleable(),
                TextColumn::make('total_duration_seconds')
                    ->label('Duration')
                    ->formatStateUsing(function (?int $state): string {
                        if (! $state) {
                            return '-';
                        }
                        $minutes = floor($state / 60);
                        $seconds = $state % 60;

                        return sprintf('%d:%02d', $minutes, $seconds);
                    })
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('started_at')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                SelectFilter::make('status')
                    ->options(SessionStatus::options()),
                SelectFilter::make('user')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('completed_at', 'desc');
    }
}
