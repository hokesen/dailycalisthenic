<?php

namespace App\Filament\Resources\UserGoals\Pages;

use App\Filament\Resources\UserGoals\UserGoalResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUserGoal extends CreateRecord
{
    protected static string $resource = UserGoalResource::class;
}
