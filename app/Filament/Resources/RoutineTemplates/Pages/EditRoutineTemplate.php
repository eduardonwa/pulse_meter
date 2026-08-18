<?php

namespace App\Filament\Resources\RoutineTemplates\Pages;

use App\Filament\Resources\RoutineTemplates\RoutineTemplateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRoutineTemplate extends EditRecord
{
    protected static string $resource = RoutineTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
