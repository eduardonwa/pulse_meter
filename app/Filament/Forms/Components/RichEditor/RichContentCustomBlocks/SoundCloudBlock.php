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
                    ->label('SoundCloud URL or embed code')
                    ->placeholder('https://soundcloud.com/... or <iframe ...>')
                    ->rows(6)
                    ->required()
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
        $value = trim($config['embed'] ?? '');

        $src = static::extractSrc($value);

        $isPlaylist = str_contains($src, '/playlists/');

        return view(
            'filament.forms.components.rich-editor.rich-content-custom-blocks.soundcloud.index',
            [
                'src' => $src,
                'isPlaylist' => $isPlaylist,
            ],
        )->render();
    }

    protected static function extractSrc(string $value): string
    {
        $value = trim($value);

        // Embed completo
        if (str_contains($value, '<iframe')) {
            preg_match(
                '/src=["\']([^"\']+)["\']/i',
                $value,
                $matches
            );

            return html_entity_decode(
                $matches[1] ?? '',
                ENT_QUOTES | ENT_HTML5
            );
        }

        // URL normal de SoundCloud
        if (str_starts_with($value, 'https://soundcloud.com/')) {
            return 'https://w.soundcloud.com/player/?' . http_build_query([
                'url' => $value,
                'auto_play' => false,
                'hide_related' => false,
                'show_comments' => false,
                'show_user' => true,
                'show_reposts' => false,
                'show_teaser' => false,
            ]);
        }

        return '';
    }
}