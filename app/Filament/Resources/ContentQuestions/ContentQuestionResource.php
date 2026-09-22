<?php

namespace App\Filament\Resources\ContentQuestions;

use App\Filament\Resources\ContentQuestions\Pages\EditContentQuestion;
use App\Filament\Resources\ContentQuestions\Pages\ListContentQuestions;
use App\Filament\Resources\ContentQuestions\Schemas\ContentQuestionForm;
use App\Filament\Resources\ContentQuestions\Tables\ContentQuestionsTable;
use App\Models\ContentQuestion;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ContentQuestionResource extends Resource
{
    protected static ?string $model = ContentQuestion::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInbox;

    protected static ?string $navigationLabel = 'Received questions';

    protected static ?string $modelLabel = 'received question';

    protected static ?string $pluralModelLabel = 'received questions';

    protected static ?string $recordTitleAttribute = 'question';

    public static function form(Schema $schema): Schema
    {
        return ContentQuestionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ContentQuestionsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContentQuestions::route('/'),
            'edit' => EditContentQuestion::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['question', 'name', 'email'];
    }
}
