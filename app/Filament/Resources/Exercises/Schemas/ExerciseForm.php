<?php

namespace App\Filament\Resources\Exercises\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ExerciseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Basic Information')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Push-ups'),
                        Select::make('user_id')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->placeholder('Leave empty for system exercise')
                            ->helperText('System exercises (NULL user) are available to all users'),
                        Select::make('category')
                            ->options([
                                'push' => 'Push',
                                'pull' => 'Pull',
                                'legs' => 'Legs',
                                'core' => 'Core',
                                'full_body' => 'Full Body',
                                'cardio' => 'Cardio',
                                'flexibility' => 'Flexibility',
                            ])
                            ->placeholder('Select a category'),
                        Select::make('difficulty_level')
                            ->options([
                                'beginner' => 'Beginner',
                                'intermediate' => 'Intermediate',
                                'advanced' => 'Advanced',
                                'expert' => 'Expert',
                            ])
                            ->placeholder('Select difficulty'),
                        TextInput::make('default_duration_seconds')
                            ->numeric()
                            ->suffix('seconds')
                            ->placeholder('60')
                            ->helperText('Recommended duration for this exercise'),
                    ])->columns(2),
                Section::make('Details')
                    ->schema([
                        Textarea::make('description')
                            ->rows(3)
                            ->placeholder('Brief description of the exercise')
                            ->columnSpanFull(),
                        Textarea::make('instructions')
                            ->rows(5)
                            ->placeholder('Step-by-step instructions on how to perform this exercise')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
