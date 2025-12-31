<?php

namespace App\Filament\Resources\SessionTemplates\Pages;

use App\Filament\Resources\SessionTemplates\SessionTemplateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSessionTemplates extends ListRecords
{
    protected static string $resource = SessionTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
