<?php

namespace App\Filament\Resources\Posts\Schemas;

use App\Filament\Forms\Components\RichEditor\RichContentCustomBlocks\EnglishCallToActionBlock;
use App\Filament\Forms\Components\RichEditor\RichContentCustomBlocks\SoundCloudBlock;
use App\Filament\Forms\Components\RichEditor\RichContentCustomBlocks\SpanishCallToActionBlock;
use App\Filament\Forms\Components\RichEditor\RichContentCustomBlocks\YouTubeBlock;
use App\Models\PostTranslation;
use Filament\Forms\Components\BaseFileUpload;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class PostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns([
                'default' => 1,
                'xl' => 3
            ])
            ->components([
                Hidden::make('user_id')->default(fn () => Auth::id()),
                
                FileUpload::make('thumbnail')
                    ->label('Cover image')
                    ->image()
                    ->imageEditor()
                    ->imageEditorAspectRatioOptions([
                        '16:9'
                    ])
                    ->imageEditorViewportWidth('1600')
                    ->imageEditorViewportHeight('900')
                    ->disk('public')
                    ->visibility('public')
                    ->maxSize(8192)
                    ->saveUploadedFileUsing(
                        function (
                            BaseFileUpload $component,
                            TemporaryUploadedFile $file,
                        ): string {
                            $filename = Str::uuid() . '.webp';
                            $path = 'blog/thumbs/' . $filename;

                            $image = Image::decode($file->getRealPath())
                                ->coverDown(1600, 900)
                                ->encodeUsingFileExtension('webp', quality: 82);

                            Storage::disk('public')->put(
                                $path,
                                (string) $image,
                                'public',
                            );

                            return $path;
                        }
                    )
                    ->deleteUploadedFileUsing(
                        fn (string $file): bool =>
                            Storage::disk('public')->delete($file)
                    )
                    ->columnStart([
                        'default' => 1,
                        'xl' => 3,
                    ])
                    ->columnSpan(1)
                    ->extraInputAttributes([
                        'class' => 'img-wrapper'
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
                    ->id('post-translations')
                    ->columnSpanFull(),
            ]);
    }

    private static function translationFields(string $locale,bool $required = false): array {
        $callToActionBlock = match ($locale) {
            'es' => SpanishCallToActionBlock::class,
            'en' => EnglishCallToActionBlock::class,
            default => throw new \InvalidArgumentException(
                "Unsupported locale: {$locale}"
            ),
        };
        
        return [
            Hidden::make('locale')
                ->default($locale),

            Grid::make([
                'default' => 1,
                'xl' => 3,
            ])
            ->schema([
                Group::make()
                    ->schema([
                        TextInput::make('title')
                            ->live(debounce: 500)
                            ->afterStateUpdated(
                                fn (Set $set, ?string $state) =>
                                    $set('slug', Str::slug($state ?? ''))
                            )
                            ->required($required),

                        RichEditor::make('body')
                            ->json()
                            ->fileAttachmentsDisk('public')
                            ->fileAttachmentsDirectory("blog/posts/{$locale}")
                            ->fileAttachmentsVisibility('public')
                            ->customBlocks([
                                YouTubeBlock::class,
                                SoundCloudBlock::class,
                                $callToActionBlock,
                            ])
                            ->extraAttributes([
                                'class' => 'post-body-editor',
                            ])
                            ->required($required),
                    ])
                    ->columnSpan([
                        'default' => 1,
                        'xl' => 2,
                    ]),

                Tabs::make('Post settings')
                    ->tabs([
                        Tab::make('Settings')
                            ->schema([
                                Toggle::make('published_at')
                                    ->label('Visibility')
                                    ->formatStateUsing(
                                        fn ($state): bool => filled($state)
                                    )
                                    ->dehydrateStateUsing(
                                        fn (
                                            bool $state,
                                            ?PostTranslation $record
                                        ) =>
                                            $state
                                                ? ($record?->published_at ?? now())
                                                : null
                                    )
                                    ->extraFieldWrapperAttributes([
                                        'class' => 'visibility-toggle-field',
                                    ]),
                                TextInput::make('slug')
                                    ->unique(ignoreRecord: true)
                                    ->required($required),

                                Textarea::make('excerpt')
                                    ->required($required)
                                    ->extraInputAttributes([
                                        'style' => 'resize: none; min-height: 14rem;'
                                    ]),

                                TextInput::make('thumbnail_alt')
                                    ->label('Cover image alt text')
                                    ->maxLength(255)
                            ]),

                        Tab::make('SEO')
                            ->schema([
                                TextInput::make('meta_title')->required($required),
                                Textarea::make('meta_description')
                                    ->extraInputAttributes([
                                        'style' => 'resize: none; min-height: 14rem;'
                                    ])
                                    ->required($required),
                            ]),
                    ])
                    ->contained(false)
                    ->columnSpan([
                        'default' => 1,
                        'xl' => 1,
                    ]),
            ]),
        ];
    }
}