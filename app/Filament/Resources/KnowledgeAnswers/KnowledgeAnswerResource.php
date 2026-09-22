<?php

namespace App\Filament\Resources\KnowledgeAnswers;

use App\Filament\Resources\KnowledgeAnswers\Pages\CreateKnowledgeAnswer;
use App\Filament\Resources\KnowledgeAnswers\Pages\EditKnowledgeAnswer;
use App\Filament\Resources\KnowledgeAnswers\Pages\ListKnowledgeAnswers;
use App\Filament\Resources\KnowledgeAnswers\Schemas\KnowledgeAnswerForm;
use App\Filament\Resources\KnowledgeAnswers\Tables\KnowledgeAnswersTable;
use App\Models\KnowledgeAnswer;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class KnowledgeAnswerResource extends Resource
{
    protected static ?string $model = KnowledgeAnswer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $navigationLabel = 'Answers';

    protected static ?string $modelLabel = 'answer';

    protected static ?string $pluralModelLabel = 'answers';

    protected static ?string $recordTitleAttribute = 'question';

    public static function form(Schema $schema): Schema
    {
        return KnowledgeAnswerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return KnowledgeAnswersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKnowledgeAnswers::route('/'),
            'create' => CreateKnowledgeAnswer::route('/create'),
            'edit' => EditKnowledgeAnswer::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['question', 'answer'];
    }
}
