<?php

namespace Tests\Feature\Blog;

use App\Models\KnowledgeAnswer;
use App\Models\Post;
use App\Models\PostTranslation;
use App\Models\RoutineTemplate;
use App\Models\RoutineTemplateTranslation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ArticleSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_jev_returns_the_best_published_article(): void
    {
        config()->set('services.typesafe.key', 'test-key');
        config()->set('services.typesafe.search_min_probability', 0.60);
        config()->set('services.typesafe.search_min_confidence', 0.25);

        $article = $this->createArticle(
            title: 'Cómo practicar con metrónomo',
            slug: 'como-practicar-con-metronomo',
            excerpt: 'Aprende a subir el tempo sin perder precisión.',
        );

        $otherArticle = $this->createArticle(
            title: 'Cómo organizar una rutina',
            slug: 'como-organizar-una-rutina',
            excerpt: 'Organiza tus ejercicios diarios.',
        );

        Http::fake([
            'api.typesafe.ai/*' => Http::response([
                'answers' => [
                    'question_language' => [
                        'type' => 'choice',
                        'choice' => 'es',
                    ],
                    'best_resource' => [
                        'type' => 'choice',
                        'choice' => "article_{$article->id}",
                        'confidence' => 0.88,
                        'probabilities' => [
                            "article_{$article->id}" => 0.91,
                            "article_{$otherArticle->id}" => 0.06,
                            'no_match' => 0.03,
                        ],
                    ],
                ],
            ]),
        ]);

        $this->postJson(route('chat.search'), [
            'query' => '¿Cómo aumento el BPM sin tensarme?',
            'locale' => 'es',
        ])
            ->assertOk()
            ->assertJsonPath('matched', true)
            ->assertJsonPath('resource.type', 'article')
            ->assertJsonPath('resource.title', $article->title)
            ->assertJsonPath(
                'resource.url',
                route('blog.show', [
                    'locale' => 'es',
                    'slug' => $article->slug,
                ]),
            );

        Http::assertSent(fn ($request): bool =>
            $request->url() === 'https://api.typesafe.ai/v1/systemone'
            && $request->hasHeader('Authorization', 'Bearer test-key')
            && data_get(
                $request->data(),
                "questions.best_resource.criteria.article_{$article->id}.title",
            ) === $article->title
        );
    }

    public function test_jev_can_report_that_no_article_answers_the_question(): void
    {
        config()->set('services.typesafe.key', 'test-key');

        $article = $this->createArticle(
            title: 'Cómo practicar con metrónomo',
            slug: 'como-practicar-con-metronomo',
            excerpt: 'Aprende a subir el tempo sin perder precisión.',
        );

        Http::fake([
            'api.typesafe.ai/*' => Http::response([
                'answers' => [
                    'best_resource' => [
                        'type' => 'choice',
                        'choice' => 'no_match',
                        'confidence' => 0.95,
                        'probabilities' => [
                            "article_{$article->id}" => 0.02,
                            'no_match' => 0.98,
                        ],
                    ],
                ],
            ]),
        ]);

        $this->postJson(route('chat.search'), [
            'query' => '¿Qué pedales usó Hendrix en 1969?',
            'locale' => 'es',
        ])
            ->assertOk()
            ->assertExactJson([
                'matched' => false,
                'locale' => 'es',
            ]);
    }

    public function test_jev_can_return_a_published_routine(): void
    {
        config()->set('services.typesafe.key', 'test-key');
        config()->set('services.typesafe.search_min_probability', 0.45);
        config()->set('services.typesafe.search_min_confidence', 0.15);

        $routine = $this->createRoutine(
            title: 'Rutina de alternate picking de 20 minutos',
            slug: 'alternate-picking-20-minutos',
            summary: 'Una práctica breve para desarrollar precisión y velocidad.',
        );

        Http::fake([
            'api.typesafe.ai/*' => Http::response([
                'answers' => [
                    'best_resource' => [
                        'type' => 'choice',
                        'choice' => "routine_{$routine->id}",
                        'confidence' => 0.81,
                        'probabilities' => [
                            "routine_{$routine->id}" => 0.87,
                            'no_match' => 0.13,
                        ],
                    ],
                ],
            ]),
        ]);

        $this->postJson(route('chat.search'), [
            'query' => 'Quiero trabajar mi púa alternada durante veinte minutos.',
            'locale' => 'es',
        ])
            ->assertOk()
            ->assertJsonPath('matched', true)
            ->assertJsonPath('resource.type', 'routine')
            ->assertJsonPath('resource.title', $routine->title)
            ->assertJsonPath(
                'resource.url',
                route('routines.show', [
                    'locale' => 'es',
                    'slug' => $routine->slug,
                ]),
            );

        Http::assertSent(fn ($request): bool => data_get(
            $request->data(),
            "questions.best_resource.criteria.routine_{$routine->id}.title",
        ) === $routine->title);
    }

    public function test_jev_can_return_a_published_saved_answer(): void
    {
        config()->set('services.typesafe.key', 'test-key');
        config()->set('services.typesafe.search_min_probability', 0.45);
        config()->set('services.typesafe.search_min_confidence', 0.15);

        $answer = KnowledgeAnswer::query()->create([
            'locale' => 'es',
            'question' => '¿Cómo empiezo a practicar hybrid picking?',
            'answer' => 'Empieza alternando una nota con púa y otra con el dedo medio.',
            'published_at' => now(),
        ]);

        Http::fake([
            'api.typesafe.ai/*' => Http::response([
                'answers' => [
                    'question_language' => [
                        'type' => 'choice',
                        'choice' => 'es',
                    ],
                    'best_resource' => [
                        'type' => 'choice',
                        'choice' => "answer_{$answer->id}",
                        'confidence' => 0.91,
                        'probabilities' => [
                            "answer_{$answer->id}" => 0.94,
                            'no_match' => 0.06,
                        ],
                    ],
                ],
            ]),
        ]);

        $this->postJson(route('chat.search'), [
            'query' => '¿Qué ejercicio hago para comenzar con hybrid picking?',
            'locale' => 'es',
        ])
            ->assertOk()
            ->assertExactJson([
                'matched' => true,
                'locale' => 'es',
                'resource' => [
                    'type' => 'answer',
                    'answer' => $answer->answer,
                ],
            ]);

        Http::assertSent(fn ($request): bool => data_get(
            $request->data(),
            "questions.best_resource.criteria.answer_{$answer->id}.question",
        ) === $answer->question);
    }

    public function test_unpublished_saved_answers_are_not_searchable_or_sent_to_jev(): void
    {
        config()->set('services.typesafe.key', 'test-key');

        $answer = KnowledgeAnswer::query()->create([
            'locale' => 'es',
            'question' => '¿Cómo empiezo a practicar hybrid picking?',
            'answer' => 'Esta respuesta todavía es un borrador.',
            'published_at' => null,
        ]);

        Http::fake();

        $this->postJson(route('chat.search'), [
            'query' => '¿Cómo practico hybrid picking?',
            'locale' => 'es',
        ])
            ->assertOk()
            ->assertJsonPath('matched', false);

        Http::assertNothingSent();
    }

    public function test_jev_returns_the_translation_matching_the_question_language(): void
    {
        config()->set('services.typesafe.key', 'test-key');
        config()->set('services.typesafe.search_min_probability', 0.45);
        config()->set('services.typesafe.search_min_confidence', 0.15);

        $post = Post::query()->create([
            'user_id' => User::factory()->create()->id,
        ]);
        $spanish = $this->createArticle(
            title: 'Cómo practicar con metrónomo',
            slug: 'como-practicar-con-metronomo',
            excerpt: 'Elige una velocidad útil.',
            post: $post,
        );
        $english = $this->createArticle(
            title: 'How to practice with a metronome',
            slug: 'how-to-practice-with-a-metronome',
            excerpt: 'Choose a useful tempo.',
            locale: 'en',
            post: $post,
        );

        Http::fake([
            'api.typesafe.ai/*' => Http::response([
                'answers' => [
                    'question_language' => [
                        'type' => 'choice',
                        'choice' => 'en',
                    ],
                    'best_resource' => [
                        'type' => 'choice',
                        'choice' => "article_{$spanish->id}",
                        'confidence' => 0.84,
                        'probabilities' => [
                            "article_{$spanish->id}" => 0.82,
                            "article_{$english->id}" => 0.15,
                            'no_match' => 0.03,
                        ],
                    ],
                ],
            ]),
        ]);

        $this->postJson(route('chat.search'), [
            'query' => 'How can I practice without getting tense?',
            'locale' => 'es',
        ])
            ->assertOk()
            ->assertJsonPath('matched', true)
            ->assertJsonPath('locale', 'en')
            ->assertJsonPath('resource.title', $english->title)
            ->assertJsonPath(
                'resource.url',
                route('blog.show', [
                    'locale' => 'en',
                    'slug' => $english->slug,
                ]),
            );

        Http::assertSent(fn ($request): bool =>
            data_get(
                $request->data(),
                "questions.best_resource.criteria.article_{$spanish->id}.language",
            ) === 'es'
            && data_get(
                $request->data(),
                "questions.best_resource.criteria.article_{$english->id}.language",
            ) === 'en'
        );
    }

    public function test_a_low_probability_match_is_rejected(): void
    {
        config()->set('services.typesafe.key', 'test-key');
        config()->set('services.typesafe.search_min_probability', 0.60);

        $article = $this->createArticle(
            title: 'Cómo practicar con metrónomo',
            slug: 'como-practicar-con-metronomo',
            excerpt: 'Aprende a subir el tempo sin perder precisión.',
        );

        Http::fake([
            'api.typesafe.ai/*' => Http::response([
                'answers' => [
                    'best_resource' => [
                        'type' => 'choice',
                        'choice' => "article_{$article->id}",
                        'confidence' => 0.40,
                        'probabilities' => [
                            "article_{$article->id}" => 0.51,
                            'no_match' => 0.49,
                        ],
                    ],
                ],
            ]),
        ]);

        $this->postJson(route('chat.search'), [
            'query' => '¿Cómo mejoro?',
            'locale' => 'es',
        ])
            ->assertOk()
            ->assertExactJson([
                'matched' => false,
                'locale' => 'es',
            ]);
    }

    public function test_it_uses_a_conservative_lexical_fallback_without_an_api_key(): void
    {
        config()->set('services.typesafe.key', null);

        $article = $this->createArticle(
            title: 'Cómo practicar con metrónomo sin frustrarte',
            slug: 'como-practicar-con-metronomo',
            excerpt: 'Elige una velocidad útil sin sacrificar precisión.',
        );

        $this->postJson(route('chat.search'), [
            'query' => '¿Cómo puedo practicar con metrónomo?',
            'locale' => 'es',
        ])
            ->assertOk()
            ->assertJsonPath('matched', true)
            ->assertJsonPath('resource.type', 'article')
            ->assertJsonPath('resource.title', $article->title);

        Http::assertNothingSent();
    }

    public function test_unpublished_articles_are_not_searchable(): void
    {
        config()->set('services.typesafe.key', null);

        $this->createArticle(
            title: 'Cómo practicar con metrónomo',
            slug: 'como-practicar-con-metronomo',
            excerpt: 'Aprende a practicar con metrónomo.',
            published: false,
        );

        $this->postJson(route('chat.search'), [
            'query' => '¿Cómo practicar con metrónomo?',
            'locale' => 'es',
        ])
            ->assertOk()
            ->assertExactJson([
                'matched' => false,
                'locale' => 'es',
            ]);
    }

    public function test_unpublished_routines_are_not_searchable_or_sent_to_jev(): void
    {
        config()->set('services.typesafe.key', 'test-key');

        $this->createRoutine(
            title: 'Rutina privada de alternate picking',
            slug: 'rutina-privada-alternate-picking',
            summary: 'Una rutina que todavía no está publicada.',
            published: false,
        );

        Http::fake();

        $this->postJson(route('chat.search'), [
            'query' => 'Quiero practicar alternate picking.',
            'locale' => 'es',
        ])
            ->assertOk()
            ->assertExactJson([
                'matched' => false,
                'locale' => 'es',
            ]);

        Http::assertNothingSent();
    }

    private function createArticle(
        string $title,
        string $slug,
        string $excerpt,
        bool $published = true,
        string $locale = 'es',
        ?Post $post = null,
    ): PostTranslation {
        $post ??= Post::query()->create([
            'user_id' => User::factory()->create()->id,
        ]);

        return $post->translations()->create([
            'locale' => $locale,
            'title' => $title,
            'slug' => $slug,
            'excerpt' => $excerpt,
            'body' => [
                'type' => 'doc',
                'content' => [
                    [
                        'type' => 'paragraph',
                        'content' => [
                            ['type' => 'text', 'text' => $excerpt],
                        ],
                    ],
                ],
            ],
            'published_at' => $published ? now() : null,
        ]);
    }

    private function createRoutine(
        string $title,
        string $slug,
        string $summary,
        bool $published = true,
    ): RoutineTemplateTranslation {
        $template = RoutineTemplate::query()->create([
            'user_id' => User::factory()->create()->id,
            'type' => RoutineTemplate::TYPE_ROUTINE,
            'instrument' => 'guitar',
            'difficulty' => 'intermediate',
        ]);

        $template->steps()->create([
            'name_es' => 'Alternate picking',
            'notes_es' => 'Mantén el movimiento relajado.',
            'bpm' => 90,
            'mode' => 'timer',
            'duration_seconds' => 1200,
            'position' => 0,
        ]);

        return $template->translations()->create([
            'locale' => 'es',
            'title' => $title,
            'slug' => $slug,
            'summary' => $summary,
            'purpose' => 'Mejorar coordinación y resistencia.',
            'instructions' => 'Empieza relajado y aumenta gradualmente.',
            'meta_title' => $title,
            'meta_description' => $summary,
            'published_at' => $published ? now() : null,
        ]);
    }
}
