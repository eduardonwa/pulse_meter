<?php

namespace App\Filament\Forms\Components\RichEditor\RichContentCustomBlocks;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\TextInput;

class YouTubeBlock extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return 'youtube';
    }

    public static function getLabel(): string
    {
        return 'YouTube video';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalDescription('Insert YouTube video')
            ->schema([
                TextInput::make('url')
                    ->label('YouTube URL')
                    ->url()
                    ->required()
                    ->dehydrateStateUsing(function (?string $state): ?string {
                        if (blank($state)) {
                            return null;
                        }

                        $videoId = self::extractVideoId($state);

                        return $videoId
                            ? "https://www.youtube.com/watch?v={$videoId}"
                            : $state;
                    })
            ]);
    }

    public static function toPreviewHtml(array $config): string
    {
        return view(
            'filament.forms.components.rich-editor.rich-content-custom-blocks.you-tube.preview',
            [
                'videoId' => static::extractVideoId(
                    (string) ($config['url'] ?? '')
                ),
            ],
        )->render();
    }

    public static function toHtml(array $config, array $data): string
    {
        return view(
            'filament.forms.components.rich-editor.rich-content-custom-blocks.you-tube.index',
            [
                'videoId' => static::extractVideoId(
                    (string) ($config['url'] ?? '')
                ),
            ],
        )->render();
    }

    private static function extractVideoId(string $url): ?string
    {
        preg_match(
            '~(?:youtube\.com/(?:watch\?v=|embed/|shorts/)|youtu\.be/)([A-Za-z0-9_-]{11})~',
            $url,
            $matches
        );

        return $matches[1] ?? null;
    }
}
