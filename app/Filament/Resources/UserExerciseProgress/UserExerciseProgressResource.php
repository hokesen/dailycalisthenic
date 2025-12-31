<?php

namespace App\Filament\Resources\UserExerciseProgress;

use App\Filament\Resources\UserExerciseProgress\Pages\CreateUserExerciseProgress;
use App\Filament\Resources\UserExerciseProgress\Pages\EditUserExerciseProgress;
use App\Filament\Resources\UserExerciseProgress\Pages\ListUserExerciseProgress;
use App\Filament\Resources\UserExerciseProgress\Schemas\UserExerciseProgressForm;
use App\Filament\Resources\UserExerciseProgress\Tables\UserExerciseProgressTable;
use App\Models\UserExerciseProgress;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class UserExerciseProgressResource extends Resource
{
    protected static ?string $model = UserExerciseProgress::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'Progress';

    protected static ?string $modelLabel = 'User Progress';

    protected static ?string $pluralModelLabel = 'User Progress';

    protected static string|UnitEnum|null $navigationGroup = 'Tracking';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return UserExerciseProgressForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UserExerciseProgressTable::configure($table);
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
            'index' => ListUserExerciseProgress::route('/'),
            'create' => CreateUserExerciseProgress::route('/create'),
            'edit' => EditUserExerciseProgress::route('/{record}/edit'),
        ];
    }
}
