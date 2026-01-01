<?php

namespace App\Filament\Resources\Sessions\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SessionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Session Information')
                    ->schema([
                        Select::make('user_id')
                            ->relationship('user', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Select::make('session_template_id')
                            ->relationship('template', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder('Select a template (optional)')
                            ->helperText('Choose a template or create a custom session'),
                        TextInput::make('name')
                            ->maxLength(255)
                            ->placeholder('Custom session name'),
                        Select::make('status')
                            ->required()
                            ->default('planned')
                            ->options([
                                'planned' => 'Planned',
                                'in_progress' => 'In Progress',
                                'completed' => 'Completed',
                                'skipped' => 'Skipped',
                                'forgiven' => 'Forgiven',
                            ])
                            ->native(false),
                    ])->columns(2),
                Section::make('Timing')
                    ->schema([
                        DateTimePicker::make('started_at')
                            ->native(false)
                            ->seconds(false),
                        DateTimePicker::make('completed_at')
                            ->native(false)
                            ->seconds(false),
                        TextInput::make('total_duration_seconds')
                            ->label('Total Duration')
                            ->numeric()
                            ->suffix('seconds')
                            ->helperText('Automatically calculated from start/end time'),
                    ])->columns(3),
                Section::make('Notes')
                    ->schema([
                        Textarea::make('notes')
                            ->rows(4)
                            ->placeholder('Session notes, form feedback, or forgiveness reason')
                            ->helperText('For forgiven sessions, use this to explain the missed day')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
