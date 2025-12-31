<?php

namespace App\Filament\Resources\UserExerciseProgress\Pages;

use App\Filament\Resources\UserExerciseProgress\UserExerciseProgressResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUserExerciseProgress extends EditRecord
{
    protected static string $resource = UserExerciseProgressResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
