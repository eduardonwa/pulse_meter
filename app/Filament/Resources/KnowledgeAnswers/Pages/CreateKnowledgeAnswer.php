<?php

namespace App\Filament\Resources\KnowledgeAnswers\Pages;

use App\Filament\Resources\KnowledgeAnswers\KnowledgeAnswerResource;
use App\Models\ContentQuestion;
use Filament\Resources\Pages\CreateRecord;

class CreateKnowledgeAnswer extends CreateRecord
{
    protected static string $resource = KnowledgeAnswerResource::class;

    public ?int $contentQuestionId = null;

    public function mount(): void
    {
        parent::mount();

        $this->contentQuestionId = request()->integer('contentQuestion') ?: null;
        $question = $this->contentQuestionId
            ? ContentQuestion::query()->find($this->contentQuestionId)
            : null;

        if ($question) {
            $this->form->fill([
                'locale' => $question->locale,
                'question' => $question->question,
            ]);
        }
    }

    protected function afterCreate(): void
    {
        if (! $this->contentQuestionId) {
            return;
        }

        ContentQuestion::query()
            ->whereKey($this->contentQuestionId)
            ->update([
                'knowledge_answer_id' => $this->record->getKey(),
            ]);
    }
}
