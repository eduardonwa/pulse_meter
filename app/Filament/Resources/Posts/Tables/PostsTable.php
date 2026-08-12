<?php

namespace App\Filament\Resources\Posts\Tables;

use App\Filament\Resources\Posts\PostResource;
use App\Models\Post;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PostsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(
                fn (Builder $query): Builder =>
                    $query->with([
                        'user',
                        'spanishTranslation',
                        'englishTranslation',
                    ])
            )
            ->columns([
                TextColumn::make('display_title')
                    ->label('Title')
                    ->state(
                        fn (Post $record): string =>
                            $record->englishTranslation?->title
                            ?? $record->spanishTranslation?->title
                            ?? "Post #{$record->id}"
                    ),

                IconColumn::make('has_spanish_translation')
                    ->label('ES')
                    ->state(
                        fn (Post $record): bool =>
                            filled($record->spanishTranslation?->title)
                    )
                    ->boolean(),

                IconColumn::make('has_english_translation')
                    ->label('EN')
                    ->state(
                        fn (Post $record): bool =>
                            filled($record->englishTranslation?->title)
                    )
                    ->boolean(),

                TextColumn::make('user.name')
                    ->label('Author')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('View')
                    ->modalHeading('Post details')
                    ->extraModalFooterActions([
                        Action::make('edit')
                            ->label('Edit post')
                            ->url(fn (Post $record): string =>
                                PostResource::getUrl('edit', [
                                    'record' => $record,
                                ])
                            ),
                    ]),
                    
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}