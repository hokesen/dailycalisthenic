<?php

namespace App\Filament\Resources\UserExerciseProgress\Pages;

use App\Filament\Resources\UserExerciseProgress\UserExerciseProgressResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUserExerciseProgress extends ListRecords
{
    protected static string $resource = UserExerciseProgressResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
