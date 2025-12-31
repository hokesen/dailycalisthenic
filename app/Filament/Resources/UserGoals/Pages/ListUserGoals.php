<?php

namespace App\Filament\Resources\UserGoals\Pages;

use App\Filament\Resources\UserGoals\UserGoalResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUserGoals extends ListRecords
{
    protected static string $resource = UserGoalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
