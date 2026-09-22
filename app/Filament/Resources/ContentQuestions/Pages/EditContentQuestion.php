<?php

namespace App\Filament\Resources\ContentQuestions\Pages;

use App\Filament\Resources\ContentQuestions\ContentQuestionResource;
use App\Filament\Resources\KnowledgeAnswers\KnowledgeAnswerResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditContentQuestion extends EditRecord
{
    protected static string $resource = ContentQuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createAnswer')
                ->label('Create saved answer')
                ->icon(Heroicon::OutlinedPlus)
                ->url(fn (): string => KnowledgeAnswerResource::getUrl('create', [
                    'contentQuestion' => $this->record->getKey(),
                ]))
                ->visible(fn (): bool => blank($this->record->knowledge_answer_id)),

            Action::make('editAnswer')
                ->label('Open saved answer')
                ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                ->url(fn (): string => KnowledgeAnswerResource::getUrl('edit', [
                    'record' => $this->record->knowledge_answer_id,
                ]))
                ->visible(fn (): bool => filled($this->record->knowledge_answer_id)),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($data['status'] === 'answered' && blank($data['answered_at'])) {
            $data['answered_at'] = now();
        }

        if ($data['status'] !== 'answered') {
            $data['answered_at'] = null;
        }

        return $data;
    }
}
