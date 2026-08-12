<?php

namespace App\Services;

use App\Filament\Forms\Components\RichEditor\RichContentCustomBlocks\YouTubeBlock;
use Filament\Forms\Components\RichEditor\RichContentRenderer;

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
            ->customBlocks([
                YouTubeBlock::class,
            ])
            ->toHtml();

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
            function (array $matches) use (
                &$position,
                $headings,
            ): string {
                $heading = $headings[$position] ?? null;

                $position++;

                if ($heading === null) {
                    return $matches[0];
                }

                $attributes = preg_replace(
                    '/\s+id=(["\']).*?\1/i',
                    '',
                    $matches[2] ?? '',
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
}