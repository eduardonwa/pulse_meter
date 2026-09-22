<?php

namespace App\Services;

use App\Models\KnowledgeAnswer;
use App\Models\PostTranslation;
use App\Models\RoutineTemplateTranslation;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ArticleSearchService
{
    /**
     * @param  Collection<int, PostTranslation>  $articles
     * @param  Collection<int, RoutineTemplateTranslation>  $routines
     * @param  Collection<int, KnowledgeAnswer>  $answers
     * @return array{resource: Model|null, type: string|null, locale: string|null, provider: string, probability: float|null, confidence: float|null}
     */
    public function search(
        string $query,
        Collection $articles,
        Collection $routines,
        Collection $answers,
        string $preferredLocale,
    ): array {
        if ($articles->isEmpty() && $routines->isEmpty() && $answers->isEmpty()) {
            return $this->result(
                locale: $preferredLocale,
                provider: 'none',
            );
        }

        if (blank(config('services.typesafe.key'))) {
            return $this->lexicalSearch(
                $query,
                $articles,
                $routines,
                $answers,
                $preferredLocale,
            );
        }

        try {
            return $this->searchWithJev(
                $query,
                $articles,
                $routines,
                $answers,
                $preferredLocale,
            );
        } catch (Throwable $exception) {
            Log::warning('Jev content search failed; using lexical fallback.', [
                'error' => $exception->getMessage(),
            ]);

            return $this->lexicalSearch(
                $query,
                $articles,
                $routines,
                $answers,
                $preferredLocale,
            );
        }
    }

    /**
     * @param  Collection<int, PostTranslation>  $articles
     * @param  Collection<int, RoutineTemplateTranslation>  $routines
     * @param  Collection<int, KnowledgeAnswer>  $answers
     * @return array{resource: Model|null, type: string|null, locale: string|null, provider: string, probability: float|null, confidence: float|null}
     */
    private function searchWithJev(
        string $query,
        Collection $articles,
        Collection $routines,
        Collection $answers,
        string $preferredLocale,
    ): array {
        $articleCriteria = $articles
            ->toBase()
            ->mapWithKeys(fn (PostTranslation $article): array => [
                "article_{$article->getKey()}" => [
                    'language' => $article->locale,
                    'title' => $article->title,
                    'summary' => $article->excerpt,
                    'content' => Str::limit(
                        $this->bodyText($article->body),
                        1_500,
                    ),
                ],
            ]);

        $routineCriteria = $routines
            ->toBase()
            ->mapWithKeys(fn (RoutineTemplateTranslation $routine): array => [
                "routine_{$routine->getKey()}" => [
                    'type' => 'interactive practice routine',
                    'language' => $routine->locale,
                    'title' => $routine->title,
                    'summary' => $routine->summary,
                    'purpose' => $routine->purpose,
                    'instructions' => $routine->instructions,
                    'instrument' => $routine->routineTemplate->instrument,
                    'difficulty' => $routine->routineTemplate->difficulty,
                    'exercises' => $routine->routineTemplate->steps
                        ->map(fn ($step): array => [
                            'name' => $routine->locale === 'en'
                                ? ($step->name_en ?: $step->name_es)
                                : $step->name_es,
                            'notes' => $routine->locale === 'en'
                                ? ($step->notes_en ?: $step->notes_es)
                                : $step->notes_es,
                            'bpm' => $step->bpm,
                            'duration_seconds' => $step->duration_seconds,
                        ])
                        ->values()
                        ->all(),
                ],
            ]);

        $answerCriteria = $answers
            ->toBase()
            ->mapWithKeys(fn (KnowledgeAnswer $answer): array => [
                "answer_{$answer->getKey()}" => [
                    'type' => 'direct saved answer',
                    'language' => $answer->locale,
                    'question' => $answer->question,
                    'answer' => Str::limit($answer->answer, 1_500),
                ],
            ]);

        $criteria = $articleCriteria
            ->merge($routineCriteria)
            ->merge($answerCriteria)
            ->put(
                'no_match',
                'None of the articles, routines, or saved answers provides useful guidance for the reader’s underlying goal.',
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
                    'instruction' => 'Choose the saved answer, article, or interactive routine that would most usefully address the reader’s underlying goal, even when the question is phrased differently. Prefer a saved answer when it directly answers the question. Prefer a matching routine when the reader asks for a practice plan, exercises, a duration, or a concrete session. Prefer an article when the reader asks for a broader explanation or concept. Choose no_match only when none provides relevant guidance.',
                ],
                'model' => config('services.typesafe.model', 'jev-latest'),
                'questions' => [
                    'question_language' => [
                        'type' => 'choice',
                        'instructions' => 'Which language is the reader primarily using?',
                        'criteria' => [
                            'es' => 'Spanish, including questions that use English names for music techniques.',
                            'en' => 'English, including questions that use Spanish names for music techniques.',
                            'other' => 'Another language or not enough information to decide.',
                        ],
                    ],
                    'best_resource' => [
                        'type' => 'choice',
                        'instructions' => 'Which saved answer, article, or routine would be most useful for this reader? Prefer a resource written in the same language as the question when equivalent translations exist.',
                        'criteria' => $criteria,
                    ],
                ],
            ])
            ->throw()
            ->json();

        $choice = data_get($response, 'answers.best_resource.choice');
        $detectedLocale = data_get(
            $response,
            'answers.question_language.choice',
        );
        $locale = in_array($detectedLocale, ['es', 'en'], true)
            ? $detectedLocale
            : $preferredLocale;
        $confidence = (float) data_get(
            $response,
            'answers.best_resource.confidence',
            0,
        );
        $probability = (float) data_get(
            $response,
            "answers.best_resource.probabilities.{$choice}",
            0,
        );

        if (! is_string($choice) || $choice === 'no_match') {
            return $this->result(
                locale: $locale,
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
                locale: $locale,
                provider: 'jev',
                probability: $probability,
                confidence: $confidence,
            );
        }

        $type = Str::before($choice, '_');
        $resourceId = (int) Str::after($choice, '_');
        $resource = match ($type) {
            'article' => $articles->firstWhere('id', $resourceId),
            'routine' => $routines->firstWhere('id', $resourceId),
            'answer' => $answers->firstWhere('id', $resourceId),
            default => null,
        };

        if ($resource) {
            $resource = $this->translationForLocale(
                $resource,
                $type,
                $locale,
                $articles,
                $routines,
                $answers,
            );
        }

        return $this->result(
            resource: $resource,
            type: $resource ? $type : null,
            locale: $resource?->locale ?? $locale,
            provider: 'jev',
            probability: $probability,
            confidence: $confidence,
        );
    }

    /**
     * @param  Collection<int, PostTranslation>  $articles
     * @param  Collection<int, RoutineTemplateTranslation>  $routines
     * @param  Collection<int, KnowledgeAnswer>  $answers
     * @return array{resource: Model|null, type: string|null, locale: string|null, provider: string, probability: float|null, confidence: float|null}
     */
    private function lexicalSearch(
        string $query,
        Collection $articles,
        Collection $routines,
        Collection $answers,
        string $preferredLocale,
    ): array {
        $tokens = $this->tokens($query);

        if ($tokens === []) {
            return $this->result(
                locale: $preferredLocale,
                provider: 'lexical',
            );
        }

        $candidates = $articles
            ->map(fn (PostTranslation $article): array => [
                'resource' => $article,
                'type' => 'article',
                'title' => (string) $article->title,
                'summary' => (string) $article->excerpt,
                'body' => $this->bodyText($article->body),
            ])
            ->concat($routines->map(fn (RoutineTemplateTranslation $routine): array => [
                'resource' => $routine,
                'type' => 'routine',
                'title' => (string) $routine->title,
                'summary' => implode(' ', array_filter([
                    $routine->summary,
                    $routine->purpose,
                    $routine->instructions,
                ])),
                'body' => $routine->routineTemplate->steps
                    ->map(fn ($step): string => implode(' ', array_filter([
                        $step->name_es,
                        $step->name_en,
                        $step->notes_es,
                        $step->notes_en,
                    ])))
                    ->implode(' '),
            ]))
            ->concat($answers->map(fn (KnowledgeAnswer $answer): array => [
                'resource' => $answer,
                'type' => 'answer',
                'title' => (string) $answer->question,
                'summary' => (string) $answer->answer,
                'body' => '',
            ]));

        $ranked = $candidates
            ->map(function (array $candidate) use ($tokens): array {
                $title = $this->normalize($candidate['title']);
                $excerpt = $this->normalize($candidate['summary']);
                $body = $this->normalize($candidate['body']);

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

                return [...$candidate, ...compact('matches', 'score')];
            })
            ->sortByDesc('score')
            ->first();

        if (
            ! $ranked
            || $ranked['score'] < 4
            || $ranked['matches'] / count($tokens) < 0.5
        ) {
            return $this->result(
                locale: $preferredLocale,
                provider: 'lexical',
            );
        }

        return $this->result(
            resource: $ranked['resource'],
            type: $ranked['type'],
            locale: $ranked['resource']->locale,
            provider: 'lexical',
        );
    }

    /**
     * @param  Collection<int, PostTranslation>  $articles
     * @param  Collection<int, RoutineTemplateTranslation>  $routines
     * @param  Collection<int, KnowledgeAnswer>  $answers
     */
    private function translationForLocale(
        Model $resource,
        string $type,
        string $locale,
        Collection $articles,
        Collection $routines,
        Collection $answers,
    ): Model {
        return match ($type) {
            'article' => $articles->first(
                fn (PostTranslation $article): bool =>
                    $article->post_id === $resource->post_id
                    && $article->locale === $locale,
            ) ?? $resource,
            'routine' => $routines->first(
                fn (RoutineTemplateTranslation $routine): bool =>
                    $routine->routine_template_id
                        === $resource->routine_template_id
                    && $routine->locale === $locale,
            ) ?? $resource,
            'answer' => $answers->first(
                fn (KnowledgeAnswer $answer): bool =>
                    $answer->id === $resource->id
                    && $answer->locale === $locale,
            ) ?? $resource,
            default => $resource,
        };
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
     * @return array{resource: Model|null, type: string|null, locale: string|null, provider: string, probability: float|null, confidence: float|null}
     */
    private function result(
        ?Model $resource = null,
        ?string $type = null,
        ?string $locale = null,
        string $provider = 'none',
        ?float $probability = null,
        ?float $confidence = null,
    ): array {
        return compact(
            'resource',
            'type',
            'locale',
            'provider',
            'probability',
            'confidence',
        );
    }
}
