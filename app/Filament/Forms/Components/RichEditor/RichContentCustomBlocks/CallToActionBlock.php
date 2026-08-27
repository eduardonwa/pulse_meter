<?php

namespace App\Filament\Forms\Components\RichEditor\RichContentCustomBlocks;

use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Storage;

abstract class CallToActionBlock extends RichContentCustomBlock
{
    abstract protected static function getLocale(): string;

    protected static function translate(string $key): string
    {
        return trans(
            "blog.cta.{$key}",
            locale: static::getLocale(),
        );
    }

    public static function getId(): string
    {
        return 'call-to-action';
    }

    public static function getLabel(): string
    {
        return 'Call to action';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalHeading('Configurar call to action')
            ->modalWidth(Width::ThreeExtraLarge)
            ->schema([
                Select::make('type')
                    ->label('Tipo de CTA')
                    ->options([
                        'subscription' => 'Suscripción a Dorelog',
                        'resource' => 'Recurso del artículo',
                    ])
                    ->default('subscription')
                    ->live()
                    ->afterStateUpdated(function (string $state, Set $set): void {
                        $set(
                            'heading',
                            static::translate("{$state}.heading"),
                        );
                        $set(
                            'button_label',
                            static::translate("{$state}.button_label"),
                        );
                        $set(
                            'button_url',
                            $state === 'subscription' ? '/register' : '/routines',
                        );
                    })
                    ->required(),

                TextInput::make('eyebrow')
                    ->label('Etiqueta')
                    ->default(
                        fn (): string => static::translate('resource.eyebrow')
                    )
                    ->visible(
                        fn (Get $get): bool => $get('type') === 'resource'
                    )
                    ->required(
                        fn (Get $get): bool => $get('type') === 'resource'
                    ),

                TextInput::make('heading')
                    ->label('Título')
                    ->default(
                        fn (): string => static::translate(
                            'subscription.heading'
                        )
                    )
                    ->required(),

                RichEditor::make('benefits')
                    ->label('Beneficios')
                    ->default(
                        fn (): string => static::translate(
                            'subscription.benefits'
                        )
                    )
                    ->toolbarButtons([
                        ['bold', 'italic'],
                        ['bulletList', 'orderedList'],
                        ['undo', 'redo'],
                    ])
                    ->visible(
                        fn (Get $get): bool => ($get('type') ?? 'subscription')
                            === 'subscription'
                    )
                    ->required(
                        fn (Get $get): bool => ($get('type') ?? 'subscription')
                            === 'subscription'
                    ),

                Textarea::make('description')
                    ->label('Descripción')
                    ->default(
                        fn (): string => static::translate(
                            'resource.description'
                        )
                    )
                    ->rows(3)
                    ->visible(
                        fn (Get $get): bool => $get('type') === 'resource'
                    )
                    ->required(
                        fn (Get $get): bool => $get('type') === 'resource'
                    ),

                TextInput::make('stat_1')
                    ->label('Dato 1')
                    ->placeholder('22 min')
                    ->visible(
                        fn (Get $get): bool => $get('type') === 'resource'
                    ),

                TextInput::make('stat_2')
                    ->label('Dato 2')
                    ->placeholder('5 ejercicios')
                    ->visible(
                        fn (Get $get): bool => $get('type') === 'resource'
                    ),

                TextInput::make('stat_3')
                    ->label('Dato 3')
                    ->placeholder('5 días')
                    ->visible(
                        fn (Get $get): bool => $get('type') === 'resource'
                    ),

                FileUpload::make('cover_image')
                    ->label('Portada')
                    ->image()
                    ->disk('public')
                    ->directory('blog/cta/' . static::getLocale())
                    ->visibility('public')
                    ->imageEditor()
                    ->imageEditorAspectRatioOptions(['4:5'])
                    ->visible(
                        fn (Get $get): bool => $get('type') === 'resource'
                    ),

                TextInput::make('cover_alt')
                    ->label('Texto alternativo de la portada')
                    ->visible(
                        fn (Get $get): bool => $get('type') === 'resource'
                            && filled($get('cover_image'))
                    )
                    ->required(
                        fn (Get $get): bool => $get('type') === 'resource'
                            && filled($get('cover_image'))
                    ),

                TextInput::make('button_label')
                    ->label('Texto del botón')
                    ->default(
                        fn (): string => static::translate(
                            'subscription.button_label'
                        )
                    )
                    ->required(),

                TextInput::make('button_url')
                    ->label('URL del botón')
                    ->default('/register')
                    ->required(),

                Select::make('button_color')
                    ->label('Color del botón')
                    ->options([
                        'dark' => 'Oscuro',
                        'accent' => 'Acento',
                        'red' => 'Rojo',
                        'yellow' => 'Amarillo',
                        'blue' => 'Azul',
                        'green' => 'Verde',
                    ])
                    ->default('dark')
                    ->required(),

                TextInput::make('supporting_text')
                    ->label('Nota de apoyo')
                    ->default(
                        fn (): string => static::translate(
                            'resource.supporting_text'
                        )
                    )
                    ->visible(
                        fn (Get $get): bool => $get('type') === 'resource'
                    ),
            ]);
    }

    public static function toPreviewHtml(array $config): string
    {
        return view('filament.forms.components.rich-editor.rich-content-custom-blocks.call-to-action.index', [
            ...$config,
            'coverImageUrl' => static::getCoverImageUrl($config),
            'isPreview' => true,
        ])->render();
    }

    public static function toHtml(array $config, array $data): string
    {
        return view('filament.forms.components.rich-editor.rich-content-custom-blocks.call-to-action.index', [
            ...$config,
            'coverImageUrl' => static::getCoverImageUrl($config),
            'isPreview' => false,
        ])->render();
    }

    private static function getCoverImageUrl(array $config): ?string
    {
        $path = $config['cover_image'] ?? null;

        return filled($path)
            ? Storage::disk('public')->url($path)
            : null;
    }
}
