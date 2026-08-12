<?php

namespace App\Filament\Resources\Posts;

use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Filament\Resources\Posts\Pages\ListPosts;
use App\Filament\Resources\Posts\Schemas\PostForm;
use App\Filament\Resources\Posts\Tables\PostsTable;
use App\Models\Post;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'id';

    public static function getRecordTitle(?Model $record): ?string
    {
        if (! $record instanceof Post) {
            return null;
        }

        return $record->englishTranslation?->title
            ?? $record->spanishTranslation?->title
            ?? "Post #{$record->getKey()}";
    }

    public static function form(Schema $schema): Schema
    {
        return PostForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PostsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPosts::route('/'),
            'create' => CreatePost::route('/create'),
            'edit' => EditPost::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return [
            'spanishTranslation.title',
            'spanishTranslation.slug',
            'englishTranslation.title',
            'englishTranslation.slug',
        ];
    }

    public static function getGlobalSearchResultTitle(
        Model $record,
    ): string {
        return $record->translations
            ->firstWhere('locale', app()->getLocale())
            ?->title
            ?? $record->translations
                ->firstWhere('locale', 'en')
                ?->title
            ?? $record->translations
                ->firstWhere('locale', 'es')
                ?->title
            ?? "Post #{$record->getKey()}";
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()
            ->with('translations');
    }
}
