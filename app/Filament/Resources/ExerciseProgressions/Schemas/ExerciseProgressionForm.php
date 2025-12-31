<?php

namespace App\Filament\Resources\ExerciseProgressions\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ExerciseProgressionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('exercise_id')
                    ->relationship('exercise', 'name')
                    ->required(),
                Select::make('easier_exercise_id')
                    ->relationship('easierExercise', 'name'),
                TextInput::make('harder_exercise_id')
                    ->numeric(),
                TextInput::make('order')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('progression_path_name'),
            ]);
    }
}
