<?php

namespace App\Filament\Resources\KnowledgeAnswers\Pages;

use App\Filament\Resources\KnowledgeAnswers\KnowledgeAnswerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListKnowledgeAnswers extends ListRecords
{
    protected static string $resource = KnowledgeAnswerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
