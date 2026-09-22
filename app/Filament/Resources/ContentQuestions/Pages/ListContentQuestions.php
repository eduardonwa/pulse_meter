<?php

namespace App\Filament\Resources\ContentQuestions\Pages;

use App\Filament\Resources\ContentQuestions\ContentQuestionResource;
use Filament\Resources\Pages\ListRecords;

class ListContentQuestions extends ListRecords
{
    protected static string $resource = ContentQuestionResource::class;
}
