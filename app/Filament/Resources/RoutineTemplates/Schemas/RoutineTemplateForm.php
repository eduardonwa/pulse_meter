<?php

namespace App\Filament\Resources\RoutineTemplates\Schemas;

use App\Filament\Forms\Components\AlphaTexPreview;
use App\Models\RoutineTemplateTranslation;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class RoutineTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make([
                    'default' => 1,
                    'lg' => 2
                ])->schema([
                    FileUpload::make('cover_image')
                        ->image()
                        ->disk('public')
                        ->directory('routines/covers')
                        ->visibility('public')
                        ->panelAspectRatio('4:5')
                        ->panelLayout('integrated')
                        ->imageEditor()
                        ->imageEditorAspectRatioOptions([
                            '4:5',
                        ]),

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
                        ->id('routine-template-translations'),
                ])
                ->columnSpanFull(),

                Repeater::make('steps')
                    ->relationship('steps')
                    ->orderColumn('position')
                    ->extraAttributes([
                        'class' => 'routine-steps-repeater'
                    ])
                    ->grid([
                        'default' => 1,
                        'lg' => 2,
                    ])
                    ->compact()
                    ->schema([
                        Tabs::make('Exercise translations')
                            ->extraAttributes([
                                'class' => 'exercise-language-tabs',
                            ])
                            ->contained(false)
                            ->tabs([
                                Tab::make('Español')
                                    ->schema([
                                        TextInput::make('name_es')
                                            ->label('Ejercicio')
                                            ->hiddenLabel()
                                            ->placeholder('Nombre ejercicio')
                                            ->required(),

                                        Textarea::make('notes_es')
                                            ->label('Notas')
                                            ->hiddenLabel()
                                            ->placeholder('Notas'),
                                    ]),

                                Tab::make('English')
                                    ->schema([
                                        TextInput::make('name_en')
                                            ->label('Name')
                                            ->hiddenLabel()
                                            ->placeholder('Exercise'),

                                        Textarea::make('notes_en')
                                            ->label('Notes')
                                            ->hiddenLabel()
                                            ->placeholder('Notes'),
                                    ]),
                            ])
                            ->columnSpanFull(),

                        Hidden::make('bpm')
                            ->default(120),

                        Hidden::make('mode')
                            ->default('timer'),

                        Hidden::make('duration_seconds'),

                        Hidden::make('alpha_tex'),
                    ])
                    ->reorderable()
                    ->defaultItems(1)
                    ->columnSpanFull()
                    ->extraItemActions([
                        Action::make('editPattern')
                            ->label('Edit pattern')
                            ->icon(Heroicon::MusicalNote)
                            ->modalHeading('Edit AlphaTab pattern')
                            ->modalWidth('7xl')
                            ->schema([
                                AlphaTexPreview::make('alpha_tex_preview')
                                    ->alphaTexField('alpha_tex')
                                    ->bpmField('bpm')
                                    ->titleField('alpha_tex_title')
                                    ->trackField('alpha_tex_track')
                                    ->instrumentField('alpha_tex_instrument')
                                    ->hiddenLabel(),
                                Grid::make([
                                    'default' => 1,
                                    'lg' => 2
                                ])
                                ->schema([
                                    Grid::make(1)
                                        ->schema([
                                            Fieldset::make('Playback')
                                                ->schema([
                                                    Grid::make([
                                                        'default' => 1,
                                                        'sm' => 3
                                                    ])->schema([
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
                                                    ])
                                                    ->columnSpanFull()
                                                ])
                                    ]),

                                    Grid::make(1)
                                        ->schema([
                                            Fieldset::make('Pattern metadata')
                                                ->schema([
                                                    TextInput::make('alpha_tex_title')
                                                        ->label('Title')
                                                        ->hiddenLabel()
                                                        ->columnSpanFull()
                                                        ->placeholder('Title')
                                                        ->live(debounce: 300),
                                                    TextInput::make('alpha_tex_track')
                                                        ->label('Track')
                                                        ->hiddenLabel()
                                                        ->placeholder('Track')
                                                        ->live(debounce: 300),
                                                    Select::make('alpha_tex_instrument')
                                                        ->label('Instrument')
                                                        ->hiddenLabel()
                                                        ->placeholder('Instrument')
                                                        ->options([
                                                            24 => 'Nylon guitar',
                                                            25 => 'Steel guitar',
                                                            26 => 'Jazz guitar',
                                                            27 => 'Clean guitar',
                                                        ])
                                                        ->default(25)
                                                        ->required()
                                                        ->live(debounce: 300),
                                                ])
                                    ]),
                                ]),
                                Textarea::make('alpha_tex')
                                    ->label('AlphaTex pattern')
                                    ->rows(12)
                                    ->extraInputAttributes([
                                        'class' => 'alpha-tex-editor__textarea',
                                    ])
                                    ->live(debounce: 300),
                            ])
                            ->fillForm(function (
                                array $arguments,
                                Repeater $component,
                            ): array {
                                $item = $component->getRawItemState($arguments['item']);
                                $alphaTex = $item['alpha_tex'] ?? '';

                                return [
                                    'alpha_tex' => self::alphaTexBody($alphaTex),
                                    'alpha_tex_title' => self::alphaTexDirective(
                                        $alphaTex,
                                        'title',
                                    ),
                                    'alpha_tex_track' => self::alphaTexDirective(
                                        $alphaTex,
                                        'track',
                                    ),
                                    'alpha_tex_instrument' => (int) (
                                        self::alphaTexDirective(
                                            $alphaTex,
                                            'instrument',
                                        ) ?? 25
                                    ),
                                    'bpm' => $item['bpm'] ?? 120,
                                    'mode' => $item['mode'] ?? 'timer',
                                    'duration_seconds' => $item['duration_seconds'] ?? null,
                                ];
                            })
                            ->action(function (
                                array $arguments,
                                array $data,
                                Repeater $component,
                            ): void {
                                $state = $component->getState();
                                $item = $arguments['item'];

                                $state[$item]['alpha_tex'] = self::buildAlphaTex(
                                    body: $data['alpha_tex'] ?? '',
                                    title: $data['alpha_tex_title'] ?? null,
                                    track: $data['alpha_tex_track'] ?? null,
                                    instrument: (int) $data['alpha_tex_instrument'],
                                );
                                $state[$item]['bpm'] = $data['bpm'];
                                $state[$item]['mode'] = $data['mode'];
                                $state[$item]['duration_seconds']
                                    = $data['duration_seconds'] ?? null;

                                $component->state($state);
                            }),
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

    private static function alphaTexDirective(
        string $alphaTex,
        string $directive,
    ): ?string {
        $directive = preg_quote($directive, '/');

        if ($directive === 'instrument') {
            preg_match(
                "/^\s*\\\\{$directive}\s+(\d+)\s*$/mi",
                $alphaTex,
                $matches,
            );

            return $matches[1] ?? null;
        }

        preg_match(
            "/^\s*\\\\{$directive}\s+\"((?:\\\\.|[^\"])*)\"\s*$/mi",
            $alphaTex,
            $matches,
        );

        if (! isset($matches[1])) {
            return null;
        }

        return preg_replace_callback(
            '/\\\\(["\\\\])/',
            fn (array $match): string => $match[1],
            $matches[1],
        );
    }

    private static function alphaTexBody(string $alphaTex): string
    {
        return trim((string) preg_replace(
            '/^\s*\\\\(?:tempo|title|track|instrument)\b[^\r\n]*(?:\R|$)/mi',
            '',
            $alphaTex,
        ));
    }

    private static function buildAlphaTex(
        string $body,
        ?string $title,
        ?string $track,
        int $instrument,
    ): ?string {
        $body = self::alphaTexBody($body);

        if ($body === '') {
            return null;
        }

        $escape = fn (string $value): string => str_replace(
            ['\\', '"'],
            ['\\\\', '\\"'],
            $value,
        );

        $directives = [];

        if (filled($title)) {
            $directives[] = sprintf('\\title "%s"', $escape($title));
        }

        if (filled($track)) {
            $directives[] = sprintf('\\track "%s"', $escape($track));
        }

        $directives[] = "\\instrument {$instrument}";

        return implode("\n", $directives) . "\n\n{$body}";
    }

    private static function translationFields(string $locale,bool $required,): array {
        $requiredWhenTranslated = $required
            ? true
            : fn (Get $get): bool =>
                filled($get('title'));

        return [
            Hidden::make('locale')
                ->default($locale),

            Toggle::make('published_at')
                ->label(
                    fn (Get $get): string =>
                        $get('published_at')
                            ? 'Unpublish'
                            : 'Publish'
                )
                ->live()
                ->columnSpanFull()
                ->extraFieldWrapperAttributes([
                    'class' => 'published-toggle',
                ])
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

            Grid::make([
                'default' => 1,
                'lg' => 2
            ])->schema([
                TextInput::make('slug')
                    ->required($requiredWhenTranslated)
                    ->dehydrateStateUsing(
                        fn (?string $state): string =>
                            Str::slug($state ?? '')
                    ),
    
                TextInput::make('cover_alt')
                    ->label('Cover image alt text')
                    ->maxLength(255),
            ]),

            Tabs::make('Routine content')
                ->extraAttributes([
                    'class' => 'routine-content-tabs',
                ])
                ->tabs([
                    Tab::make('Summary')
                        ->extraAttributes([
                            'class' => 'routine-content-tab',
                        ])
                        ->schema([
                            Textarea::make('summary')
                                ->required($requiredWhenTranslated)
                                ->rows(8),
                        ]),

                    Tab::make('Purpose')
                        ->extraAttributes([
                            'class' => 'routine-content-tab',
                        ])
                        ->schema([
                            Textarea::make('purpose')
                                ->rows(8),
                        ]),

                    Tab::make('Instructions')
                        ->extraAttributes([
                            'class' => 'routine-content-tab',
                        ])
                        ->schema([
                            Textarea::make('instructions')
                                ->rows(8),
                        ]),
                ])
                ->contained(false),
    
            TextInput::make('meta_title')
                ->required($requiredWhenTranslated)
                ->maxLength(255),

            Textarea::make('meta_description')
                ->required($requiredWhenTranslated)
                ->rows(3),
        ];
    }
}
