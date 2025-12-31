<?php

namespace App\Filament\Resources\SessionTemplates\Pages;

use App\Filament\Resources\SessionTemplates\SessionTemplateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSessionTemplate extends EditRecord
{
    protected static string $resource = SessionTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
