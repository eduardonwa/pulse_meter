<?php

namespace App\Filament\Resources\ContentQuestions\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ContentQuestionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('question')
                    ->searchable()
                    ->limit(80)
                    ->wrap(),

                TextColumn::make('locale')
                    ->label('Language')
                    ->badge(),

                TextColumn::make('name')
                    ->placeholder('—')
                    ->searchable(),

                TextColumn::make('email')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'answered' => 'success',
                        'dismissed' => 'gray',
                        default => 'warning',
                    }),

                TextColumn::make('knowledgeAnswer.question')
                    ->label('Saved answer')
                    ->limit(45)
                    ->placeholder('—'),

                TextColumn::make('created_at')
                    ->label('Received')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'answered' => 'Answered',
                        'dismissed' => 'Dismissed',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
