<?php

namespace App\Services;

use App\Filament\Forms\Components\RichEditor\RichContentCustomBlocks\EnglishCallToActionBlock;
use App\Filament\Forms\Components\RichEditor\RichContentCustomBlocks\SoundCloudBlock;
use App\Filament\Forms\Components\RichEditor\RichContentCustomBlocks\SpanishCallToActionBlock;
use App\Filament\Forms\Components\RichEditor\RichContentCustomBlocks\YouTubeBlock;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Illuminate\Support\Facades\Storage;

class PostContentRenderer
{
    public function render(array $body): array
    {
        $headings = [];

        $this->collectHeadings(
            nodes: $body['content'] ?? [],
            headings: $headings,
        );

        $html = RichContentRenderer::make($body)
            ->fileAttachmentsDisk('public')
            ->fileAttachmentsVisibility('public')
            ->customBlocks([
                YouTubeBlock::class,
                SpanishCallToActionBlock::class,
                EnglishCallToActionBlock::class,
                SoundCloudBlock::class,
            ])
            ->toHtml();

        $html = $this->addImageDimensions($html);

        $html = $this->addHeadingIds(
            html: $html,
            headings: $headings,
        );

        return [
            'html' => $html,
            'tableOfContents' => array_values(
                array_filter(
                    $headings,
                    fn (array $heading): bool => filled($heading['title']),
                )
            ),
        ];
    }

    private function collectHeadings(
        array $nodes,
        array &$headings,
    ): void {
        $occurrences = [];

        foreach ($nodes as $node) {
            $level = $node['attrs']['level'] ?? null;

            if (
                ($node['type'] ?? null) !== 'heading' ||
                ! in_array($level, [2, 3], true)
            ) {
                continue;
            }

            $title = trim(
                $this->extractText($node['content'] ?? [])
            );

            $key = mb_strtolower($title);

            $occurrences[$key] = ($occurrences[$key] ?? 0) + 1;

            $headings[] = [
                'level' => $level,
                'title' => $title,
                'id' => 'link-' . substr(
                    hash(
                        'xxh3',
                        $key . '|' . $occurrences[$key],
                    ),
                    0,
                    10,
                ),
            ];
        }
    }

    private function extractText(array $nodes): string
    {
        $text = '';

        foreach ($nodes as $node) {
            $text .= $node['text'] ?? '';

            if (isset($node['content'])) {
                $text .= $this->extractText($node['content']);
            }
        }

        return $text;
    }

    private function addHeadingIds(
        string $html,
        array $headings,
    ): string {
        $position = 0;

        return preg_replace_callback(
            '/<h([23])([^>]*)>/i',

            function (array $matches) use (&$position, $headings): string {
                $attributes = $matches[2] ?? '';

                // Ignorar headings pertenecientes a los CTA.
                if (str_contains($attributes, 'post--cta__')) {
                    return $matches[0];
                }

                $heading = $headings[$position] ?? null;
                $position++;

                if ($heading === null) {
                    return $matches[0];
                }

                $attributes = preg_replace(
                    '/\s+id=(["\']).*?\1/i',
                    '',
                    $attributes,
                );

                return sprintf(
                    '<h%s id="%s"%s>',
                    $matches[1],
                    $heading['id'],
                    $attributes,
                );
            },
            $html,
        );
    }

    private function addImageDimensions(string $html): string
    {
        return preg_replace_callback(
            '/<img\b[^>]*>/i',
            function (array $matches): string {
                $tag = $matches[0];

                // No reemplazar dimensiones existentes.
                if (
                    preg_match('/\swidth=(["\']).*?\1/i', $tag) &&
                    preg_match('/\sheight=(["\']).*?\1/i', $tag)
                ) {
                    return $tag;
                }

                if (
                    ! preg_match(
                        '/\sdata-id=(["\'])(.*?)\1/i',
                        $tag,
                        $dataId,
                    )
                ) {
                    return $tag;
                }

                $path = html_entity_decode(
                    $dataId[2],
                    ENT_QUOTES | ENT_HTML5,
                );

                $disk = Storage::disk('public');

                if (! $disk->exists($path)) {
                    return $tag;
                }

                $dimensions = @getimagesize($disk->path($path));

                if ($dimensions === false) {
                    return $tag;
                }

                [$width, $height] = $dimensions;

                // Elimina una dimensión suelta antes de añadir ambas.
                $tag = preg_replace(
                    '/\s(?:width|height)=(["\']).*?\1/i',
                    '',
                    $tag,
                );

                return preg_replace(
                    '/>$/',
                    sprintf(
                        ' width="%d" height="%d">',
                        $width,
                        $height,
                    ),
                    $tag,
                );
            },
            $html,
        );
    }
}