<?php

namespace App\Filament\Resources\ExerciseProgressions\Pages;

use App\Filament\Resources\ExerciseProgressions\ExerciseProgressionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListExerciseProgressions extends ListRecords
{
    protected static string $resource = ExerciseProgressionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
