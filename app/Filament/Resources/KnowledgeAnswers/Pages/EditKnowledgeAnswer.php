<?php

namespace App\Filament\Resources\KnowledgeAnswers\Pages;

use App\Filament\Resources\KnowledgeAnswers\KnowledgeAnswerResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditKnowledgeAnswer extends EditRecord
{
    protected static string $resource = KnowledgeAnswerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
