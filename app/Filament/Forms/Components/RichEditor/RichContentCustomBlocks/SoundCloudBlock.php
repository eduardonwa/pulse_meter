<?php

namespace App\Filament\Forms\Components\RichEditor\RichContentCustomBlocks;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Textarea;

class SoundCloudBlock extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return 'soundcloud';
    }

    public static function getLabel(): string
    {
        return 'SoundCloud';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalHeading('Insert SoundCloud')
            ->modalDescription('Paste the embed code from SoundCloud.')
            ->schema([
                Textarea::make('embed')
                    ->label('SoundCloud embed code')
                    ->placeholder('<iframe src="https://w.soundcloud.com/player/?url=..."></iframe>')
                    ->rows(6)
                    ->required(),
            ]);
    }

    public static function toPreviewHtml(array $config): string
    {
        return view(
            'filament.forms.components.rich-editor.rich-content-custom-blocks.soundcloud.preview',
            [
                'src' => static::extractSrc($config['embed'] ?? ''),
            ],
        )->render();
    }

    public static function toHtml(array $config, array $data): string
    {
        return view(
            'filament.forms.components.rich-editor.rich-content-custom-blocks.soundcloud.index',
            [
                'src' => static::extractSrc($config['embed'] ?? ''),
            ],
        )->render();
    }

    protected static function extractSrc(string $embed): string
    {
        preg_match(
            '/src=["\']([^"\']+)["\']/i',
            $embed,
            $matches
        );

        return html_entity_decode(
            $matches[1] ?? '',
            ENT_QUOTES | ENT_HTML5
        );
    }
}