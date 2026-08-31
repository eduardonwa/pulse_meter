<?php

namespace App\Filament\Resources\RoutineTemplates\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RoutineTemplatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('englishTranslation.title')
                    ->label('Routine name')
                    ->placeholder('No English title')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('translations.locale')
                    ->label('Languages')
                    ->badge()
                    ->formatStateUsing(
                        fn (string $state): string => strtoupper($state)
                    ),

                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(
                        fn (string $state): string => match ($state) {
                            'weekly_challenge' => 'Weekly challenge',
                            default => 'Routine',
                        }
                    )
                    ->sortable(),

                TextColumn::make('instrument')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->sortable(),

                TextColumn::make('difficulty')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->sortable(),
            ])
            ->filters([
                //
            ])
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
