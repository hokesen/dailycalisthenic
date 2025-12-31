<?php

namespace App\Filament\Resources\SessionTemplates\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SessionTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Template Information')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Morning Routine')
                            ->columnSpanFull(),
                        Select::make('user_id')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->placeholder('Leave empty for system template')
                            ->helperText('System templates (NULL user) are available to all users'),
                        TextInput::make('estimated_duration_minutes')
                            ->label('Estimated Duration')
                            ->numeric()
                            ->suffix('minutes')
                            ->placeholder('30')
                            ->helperText('Total estimated time for this workout'),
                        TextInput::make('default_rest_seconds')
                            ->label('Default Rest Time')
                            ->required()
                            ->numeric()
                            ->suffix('seconds')
                            ->default(60)
                            ->helperText('Rest time between exercises (can be overridden per exercise)'),
                    ])->columns(2),
                Section::make('Description & Notes')
                    ->schema([
                        Textarea::make('description')
                            ->rows(3)
                            ->placeholder('Brief description of this workout template')
                            ->columnSpanFull(),
                        Textarea::make('notes')
                            ->rows(4)
                            ->placeholder('Additional notes, tips, or instructions for this template')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
