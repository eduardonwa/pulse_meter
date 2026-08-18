<?php

namespace App\Filament\Resources\RoutineTemplates\Pages;

use App\Filament\Resources\RoutineTemplates\RoutineTemplateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRoutineTemplates extends ListRecords
{
    protected static string $resource = RoutineTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
