<?php

namespace App\Filament\Resources\UserGoals\Pages;

use App\Filament\Resources\UserGoals\UserGoalResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUserGoal extends EditRecord
{
    protected static string $resource = UserGoalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
