<?php

namespace App\Filament\Resources\KnowledgeAnswers\Schemas;

use App\Models\KnowledgeAnswer;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class KnowledgeAnswerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Select::make('locale')
                    ->label('Language')
                    ->options([
                        'es' => 'Español',
                        'en' => 'English',
                    ])
                    ->required(),

                Textarea::make('question')
                    ->label('Canonical question')
                    ->helperText('Write the clearest version of the question. JEV will match similar wording to it.')
                    ->rows(3)
                    ->required(),

                Textarea::make('answer')
                    ->label('Answer shown in chat')
                    ->helperText('This text is returned as written. JEV only selects it; it does not rewrite it.')
                    ->rows(10)
                    ->required(),

                Toggle::make('published_at')
                    ->label('Published and searchable')
                    ->formatStateUsing(fn ($state): bool => filled($state))
                    ->dehydrateStateUsing(
                        fn (bool $state, ?KnowledgeAnswer $record) =>
                            $state ? ($record?->published_at ?? now()) : null
                    ),
            ]);
    }
}
