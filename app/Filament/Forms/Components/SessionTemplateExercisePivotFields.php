<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

class SessionTemplateExercisePivotFields
{
    public static function make(mixed $defaultOrder = null): array
    {
        $fields = [
            TextInput::make('order')
                ->required()
                ->numeric()
                ->default($defaultOrder)
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
        ];

        return $fields;
    }
}
