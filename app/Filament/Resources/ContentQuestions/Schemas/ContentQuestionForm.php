<?php

namespace App\Filament\Resources\ContentQuestions\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ContentQuestionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Textarea::make('question')
                    ->disabled()
                    ->dehydrated(false)
                    ->rows(5)
                    ->columnSpanFull(),

                TextInput::make('name')
                    ->disabled()
                    ->dehydrated(false),

                TextInput::make('email')
                    ->disabled()
                    ->dehydrated(false),

                TextInput::make('locale')
                    ->label('Language')
                    ->disabled()
                    ->dehydrated(false),

                Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'answered' => 'Answered',
                        'dismissed' => 'Dismissed',
                    ])
                    ->required(),

                Select::make('knowledge_answer_id')
                    ->label('Saved answer')
                    ->relationship('knowledgeAnswer', 'question')
                    ->searchable()
                    ->preload()
                    ->helperText('Only published saved answers can appear in chat.')
                    ->columnSpanFull(),

                DateTimePicker::make('answered_at')
                    ->label('Answered at')
                    ->seconds(false),
            ]);
    }
}
