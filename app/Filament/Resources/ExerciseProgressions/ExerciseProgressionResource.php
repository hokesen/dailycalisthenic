<?php

namespace App\Filament\Resources\ExerciseProgressions;

use App\Filament\Resources\ExerciseProgressions\Pages\CreateExerciseProgression;
use App\Filament\Resources\ExerciseProgressions\Pages\EditExerciseProgression;
use App\Filament\Resources\ExerciseProgressions\Pages\ListExerciseProgressions;
use App\Filament\Resources\ExerciseProgressions\Schemas\ExerciseProgressionForm;
use App\Filament\Resources\ExerciseProgressions\Tables\ExerciseProgressionsTable;
use App\Models\ExerciseProgression;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ExerciseProgressionResource extends Resource
{
    protected static ?string $model = ExerciseProgression::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowTrendingUp;

    protected static ?string $navigationLabel = 'Progressions';

    protected static ?string $modelLabel = 'Exercise Progression';

    protected static ?string $pluralModelLabel = 'Exercise Progressions';

    protected static string|UnitEnum|null $navigationGroup = 'Calisthenics';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return ExerciseProgressionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ExerciseProgressionsTable::configure($table);
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
            'index' => ListExerciseProgressions::route('/'),
            'create' => CreateExerciseProgression::route('/create'),
            'edit' => EditExerciseProgression::route('/{record}/edit'),
        ];
    }
}
