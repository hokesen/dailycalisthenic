<?php

namespace App\Filament\Resources\ExerciseProgressions\Pages;

use App\Filament\Resources\ExerciseProgressions\ExerciseProgressionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditExerciseProgression extends EditRecord
{
    protected static string $resource = ExerciseProgressionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
