<?php

namespace App\Services;

use App\Models\PostTranslation;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ArticleSearchService
{
    /**
     * @param  Collection<int, PostTranslation>  $articles
     * @return array{article: PostTranslation|null, provider: string, probability: float|null, confidence: float|null}
     */
    public function search(string $query, Collection $articles): array
    {
        if ($articles->isEmpty()) {
            return $this->result(provider: 'none');
        }

        if (blank(config('services.typesafe.key'))) {
            return $this->lexicalSearch($query, $articles);
        }

        try {
            return $this->searchWithJev($query, $articles);
        } catch (Throwable $exception) {
            Log::warning('Jev article search failed; using lexical fallback.', [
                'error' => $exception->getMessage(),
            ]);

            return $this->lexicalSearch($query, $articles);
        }
    }

    /**
     * @param  Collection<int, PostTranslation>  $articles
     * @return array{article: PostTranslation|null, provider: string, probability: float|null, confidence: float|null}
     */
    private function searchWithJev(
        string $query,
        Collection $articles,
    ): array {
        $criteria = $articles
            ->mapWithKeys(fn (PostTranslation $article): array => [
                "article_{$article->getKey()}" => [
                    'title' => $article->title,
                    'summary' => $article->excerpt,
                    'content' => Str::limit(
                        $this->bodyText($article->body),
                        1_500,
                    ),
                ],
            ])
            ->put(
                'no_match',
                'None of the articles directly and substantially answers the question.',
            )
            ->all();

        $response = Http::baseUrl(
            rtrim((string) config('services.typesafe.url'), '/'),
        )
            ->withToken((string) config('services.typesafe.key'))
            ->acceptJson()
            ->timeout(10)
            ->retry(1, 150)
            ->post('/v1/systemone', [
                'state' => [
                    'reader_question' => $query,
                    'instruction' => 'Find an article that answers the question, not merely one that shares a broad topic.',
                ],
                'model' => config('services.typesafe.model', 'jev-latest'),
                'questions' => [
                    'best_article' => [
                        'type' => 'choice',
                        'instructions' => 'Which article directly and substantially answers the reader question?',
                        'criteria' => $criteria,
                    ],
                ],
            ])
            ->throw()
            ->json();

        $choice = data_get($response, 'answers.best_article.choice');
        $confidence = (float) data_get(
            $response,
            'answers.best_article.confidence',
            0,
        );
        $probability = (float) data_get(
            $response,
            "answers.best_article.probabilities.{$choice}",
            0,
        );

        if (! is_string($choice) || $choice === 'no_match') {
            return $this->result(
                provider: 'jev',
                probability: $probability,
                confidence: $confidence,
            );
        }

        if (
            $probability < config('services.typesafe.search_min_probability')
            || $confidence < config('services.typesafe.search_min_confidence')
        ) {
            return $this->result(
                provider: 'jev',
                probability: $probability,
                confidence: $confidence,
            );
        }

        $articleId = Str::after($choice, 'article_');
        $article = $articles->firstWhere('id', (int) $articleId);

        return $this->result(
            article: $article,
            provider: 'jev',
            probability: $probability,
            confidence: $confidence,
        );
    }

    /**
     * @param  Collection<int, PostTranslation>  $articles
     * @return array{article: PostTranslation|null, provider: string, probability: float|null, confidence: float|null}
     */
    private function lexicalSearch(
        string $query,
        Collection $articles,
    ): array {
        $tokens = $this->tokens($query);

        if ($tokens === []) {
            return $this->result(provider: 'lexical');
        }

        $ranked = $articles
            ->map(function (PostTranslation $article) use ($tokens): array {
                $title = $this->normalize((string) $article->title);
                $excerpt = $this->normalize((string) $article->excerpt);
                $body = $this->normalize($this->bodyText($article->body));

                $matches = 0;
                $score = 0;

                foreach ($tokens as $token) {
                    $matched = false;

                    if (str_contains($title, $token)) {
                        $score += 4;
                        $matched = true;
                    }

                    if (str_contains($excerpt, $token)) {
                        $score += 2;
                        $matched = true;
                    }

                    if (str_contains($body, $token)) {
                        $score++;
                        $matched = true;
                    }

                    $matches += (int) $matched;
                }

                return compact('article', 'matches', 'score');
            })
            ->sortByDesc('score')
            ->first();

        if (
            ! $ranked
            || $ranked['score'] < 4
            || $ranked['matches'] / count($tokens) < 0.5
        ) {
            return $this->result(provider: 'lexical');
        }

        return $this->result(
            article: $ranked['article'],
            provider: 'lexical',
        );
    }

    /** @return list<string> */
    private function tokens(string $value): array
    {
        $stopWords = [
            'about', 'como', 'con', 'cual', 'cuando', 'donde', 'esta',
            'este', 'hacer', 'para', 'pero', 'por', 'porque', 'que',
            'the', 'this', 'una', 'uno', 'what', 'when', 'where',
            'which', 'with', 'you', 'your',
        ];

        return collect(preg_split('/\s+/', $this->normalize($value)) ?: [])
            ->map(fn (string $token): string => trim($token, '.,;:!?¿¡()[]{}'))
            ->filter(fn (string $token): bool => strlen($token) >= 3)
            ->reject(fn (string $token): bool => in_array($token, $stopWords, true))
            ->unique()
            ->values()
            ->all();
    }

    private function normalize(string $value): string
    {
        return Str::lower(Str::ascii($value));
    }

    private function bodyText(mixed $node): string
    {
        if (! is_array($node)) {
            return '';
        }

        $text = is_string($node['text'] ?? null)
            ? $node['text'].' '
            : '';

        foreach ($node as $key => $value) {
            if ($key !== 'text' && is_array($value)) {
                $text .= $this->bodyText($value);
            }
        }

        return trim($text);
    }

    /**
     * @return array{article: PostTranslation|null, provider: string, probability: float|null, confidence: float|null}
     */
    private function result(
        ?PostTranslation $article = null,
        string $provider = 'none',
        ?float $probability = null,
        ?float $confidence = null,
    ): array {
        return compact(
            'article',
            'provider',
            'probability',
            'confidence',
        );
    }
}
