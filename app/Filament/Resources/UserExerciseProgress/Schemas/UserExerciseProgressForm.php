<?php

namespace App\Filament\Resources\UserExerciseProgress\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserExerciseProgressForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Select::make('exercise_id')
                    ->relationship('exercise', 'name')
                    ->required(),
                TextInput::make('status')
                    ->required()
                    ->default('current'),
                TextInput::make('best_sets')
                    ->numeric(),
                TextInput::make('best_reps')
                    ->numeric(),
                TextInput::make('best_duration_seconds')
                    ->numeric(),
                DateTimePicker::make('mastered_at'),
                DateTimePicker::make('started_at'),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
