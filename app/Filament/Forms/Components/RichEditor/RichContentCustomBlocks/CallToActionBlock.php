<?php

namespace App\Filament\Forms\Components\RichEditor\RichContentCustomBlocks;

use App\Models\PostTranslation;
use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\TextInput;
use Filament\Support\Enums\Width;
use Livewire\Component;

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
                TextInput::make('heading')
                    ->label('Título')
                    ->default(fn (): string => static::translate('heading'))
                    ->required(),

                RichEditor::make('benefits')
                    ->label('Beneficios')
                    ->default(fn (): string => static::translate('benefits'))
                    ->toolbarButtons([
                        ['bold', 'italic'],
                        ['bulletList', 'orderedList'],
                        ['undo', 'redo'],
                    ])
                    ->required(),

                TextInput::make('button_label')
                    ->label('Texto del botón')
                    ->default(
                        fn (): string => static::translate('button_label')
                    )
                    ->required(),

                TextInput::make('button_url')
                    ->label('URL del botón')
                    ->default('/register')
                    ->required(),
            ]);
    }

    public static function toPreviewHtml(array $config): string
    {
        return view('filament.forms.components.rich-editor.rich-content-custom-blocks.call-to-action.index', [
            ...$config,
            'isPreview' => true,
        ])->render();
    }

    public static function toHtml(array $config, array $data): string
    {
        return view('filament.forms.components.rich-editor.rich-content-custom-blocks.call-to-action.index', [
            ...$config,
            'isPreview' => false,
        ])->render();
    }
}