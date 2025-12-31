<?php

namespace App\Filament\Resources\UserGoals;

use App\Filament\Resources\UserGoals\Pages\CreateUserGoal;
use App\Filament\Resources\UserGoals\Pages\EditUserGoal;
use App\Filament\Resources\UserGoals\Pages\ListUserGoals;
use App\Filament\Resources\UserGoals\Schemas\UserGoalForm;
use App\Filament\Resources\UserGoals\Tables\UserGoalsTable;
use App\Models\UserGoal;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class UserGoalResource extends Resource
{
    protected static ?string $model = UserGoal::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTrophy;

    protected static ?string $navigationLabel = 'Goals';

    protected static ?string $modelLabel = 'User Goal';

    protected static ?string $pluralModelLabel = 'User Goals';

    protected static string|UnitEnum|null $navigationGroup = 'Tracking';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return UserGoalForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UserGoalsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUserGoals::route('/'),
            'create' => CreateUserGoal::route('/create'),
            'edit' => EditUserGoal::route('/{record}/edit'),
        ];
    }
}
