<?php

namespace App\Filament\Resources\SessionTemplates;

use App\Filament\Resources\SessionTemplates\Pages\CreateSessionTemplate;
use App\Filament\Resources\SessionTemplates\Pages\EditSessionTemplate;
use App\Filament\Resources\SessionTemplates\Pages\ListSessionTemplates;
use App\Filament\Resources\SessionTemplates\Schemas\SessionTemplateForm;
use App\Filament\Resources\SessionTemplates\Tables\SessionTemplatesTable;
use App\Models\SessionTemplate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SessionTemplateResource extends Resource
{
    protected static ?string $model = SessionTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentDuplicate;

    protected static ?string $navigationLabel = 'Templates';

    protected static ?string $modelLabel = 'Session Template';

    protected static ?string $pluralModelLabel = 'Session Templates';

    protected static string|UnitEnum|null $navigationGroup = 'Calisthenics';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return SessionTemplateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SessionTemplatesTable::configure($table);
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
            'index' => ListSessionTemplates::route('/'),
            'create' => CreateSessionTemplate::route('/create'),
            'edit' => EditSessionTemplate::route('/{record}/edit'),
        ];
    }
}
