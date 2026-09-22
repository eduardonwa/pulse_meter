<?php

namespace App\Filament\Resources\KnowledgeAnswers\Tables;

use App\Models\KnowledgeAnswer;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class KnowledgeAnswersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('question')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('locale')
                    ->label('Language')
                    ->badge(),

                IconColumn::make('published_at')
                    ->label('Published')
                    ->state(
                        fn (KnowledgeAnswer $record): bool =>
                            filled($record->published_at)
                            && $record->published_at->isPast()
                    )
                    ->boolean(),

                TextColumn::make('content_questions_count')
                    ->label('Linked questions')
                    ->counts('contentQuestions')
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
