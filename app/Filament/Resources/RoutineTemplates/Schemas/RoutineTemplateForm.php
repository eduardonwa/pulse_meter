<?php

namespace App\Filament\Resources\RoutineTemplates\Schemas;

use App\Models\RoutineTemplateTranslation;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class RoutineTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Translations')
                    ->tabs([
                        Tab::make('Español')
                            ->schema([
                                Group::make()
                                    ->relationship('spanishTranslation')
                                    ->schema(
                                        self::translationFields(
                                            locale: 'es',
                                            required: true,
                                        )
                                    ),
                            ]),

                        Tab::make('English')
                            ->schema([
                                Group::make()
                                    ->relationship(
                                        'englishTranslation',
                                        condition: fn (?array $state): bool =>
                                            filled($state['title'] ?? null),
                                    )
                                    ->schema(
                                        self::translationFields(
                                            locale: 'en',
                                            required: false,
                                        )
                                    ),
                            ]),
                    ])
                    ->contained(false)
                    ->persistTab()
                    ->id('routine-template-translations')
                    ->columnSpanFull(),

                Repeater::make('steps')
                    ->relationship('steps')
                    ->orderColumn('position')
                    ->schema([
                        Tabs::make('Exercise translations')
                            ->tabs([
                                Tab::make('Español')
                                    ->schema([
                                        TextInput::make('name_es')
                                            ->required(),

                                        Textarea::make('notes_es'),
                                    ]),

                                Tab::make('English')
                                    ->schema([
                                        TextInput::make('name_en'),

                                        Textarea::make('notes_en'),
                                    ]),
                            ])
                            ->columnSpanFull(),

                        TextInput::make('bpm')
                            ->numeric()
                            ->minValue(30)
                            ->maxValue(400)
                            ->required(),

                        Select::make('mode')
                            ->options([
                                'timer' => 'Timer',
                                'classic' => 'Classic',
                            ])
                            ->default('timer')
                            ->required()
                            ->live()
                            ->afterStateUpdated(function(
                                Set $set,
                                ?string $state,
                            ): void {
                                if ($state === 'classic') {
                                    $set('duration_seconds', null);
                                }
                            }),

                        TextInput::make('duration_seconds')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(300)
                            ->required(
                                fn (Get $get): bool => $get('mode') === 'timer'
                            )
                            ->visible(
                                fn (Get $get): bool => $get('mode') === 'timer'
                            )
                            ->dehydrateStateUsing(
                                fn ($state, Get $get) => $get('mode') === 'timer'
                                    ? $state
                                    : null
                            ),
                        Textarea::make('alpha_tex')
                            ->label('AlphaTex pattern')
                            ->rows(8)
                            ->helperText('Optional alphaTab/alphaTex pattern for this exercise.')
                    ])
                    ->defaultItems(1)
                    ->reorderable()
                    ->collapsible(),

                FileUpload::make('cover_image')
                    ->image()
                    ->disk('public')
                    ->directory('routines/covers')
                    ->visibility('public')
                    ->imageEditor()
                    ->imageEditorAspectRatioOptions([
                        '4:5',
                    ]),

                Select::make('type')
                    ->options([
                        'routine' => 'Routine',
                        'weekly_challenge' => 'Weekly challenge',
                    ])
                    ->default('routine')
                    ->live()
                    ->required(),

                Select::make('instrument')
                    ->options([
                        'guitar' => 'Guitar',
                        'bass' => 'Bass',
                        'drums' => 'Drums',
                    ])
                    ->default('guitar')
                    ->required(),

                Select::make('difficulty')
                    ->options([
                        'beginner' => 'Beginner',
                        'intermediate' => 'Intermediate',
                        'advanced' => 'Advanced',
                    ])
                    ->required(),

                TextInput::make('challenge_days')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(255)
                    ->visible(
                        fn (Get $get): bool =>
                            $get('type') === 'weekly_challenge'
                    ),

                TextInput::make('recommended_sessions')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(255)
                    ->visible(
                        fn (Get $get): bool =>
                            $get('type') === 'weekly_challenge'
                    ),
                Hidden::make('user_id')->default(fn () => Auth::id()),
            ]);
    }

    private static function translationFields(string $locale,bool $required,): array {
        $requiredWhenTranslated = $required
            ? true
            : fn (Get $get): bool =>
                filled($get('title'));

        return [
            Hidden::make('locale')
                ->default($locale),

            TextInput::make('title')
                ->live(debounce: 500)
                ->afterStateUpdated(
                    fn (Set $set, ?string $state) =>
                        $set(
                            'slug',
                            Str::slug($state ?? '')
                        )
                )
                ->required($required),

            TextInput::make('slug')
                ->required($requiredWhenTranslated)
                ->dehydrateStateUsing(
                    fn (?string $state): string =>
                        Str::slug($state ?? '')
                ),

            Textarea::make('summary')
                ->required($requiredWhenTranslated)
                ->rows(3),

            Textarea::make('purpose')
                ->rows(4),

            Textarea::make('instructions')
                ->rows(6),
    
            Toggle::make('published_at')
                ->label('Published')
                ->formatStateUsing(
                    fn ($state): bool => filled($state)
                )
                ->dehydrateStateUsing(
                    fn (
                        bool $state,
                        ?RoutineTemplateTranslation $record,
                    ) =>
                        $state
                            ? ($record?->published_at ?? now())
                            : null
                ),

            TextInput::make('cover_alt')
                ->label('Cover image alt text')
                ->maxLength(255),

            TextInput::make('meta_title')
                ->required($requiredWhenTranslated)
                ->maxLength(255),

            Textarea::make('meta_description')
                ->required($requiredWhenTranslated)
                ->rows(3),
        ];
    }
}
