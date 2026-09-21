<?php

namespace Tests\Feature\Blog;

use App\Models\Post;
use App\Models\PostTranslation;
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
                    'best_article' => [
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

        $this->postJson(route('blog.search', ['locale' => 'es']), [
            'query' => '¿Cómo aumento el BPM sin tensarme?',
        ])
            ->assertOk()
            ->assertJsonPath('matched', true)
            ->assertJsonPath('article.title', $article->title)
            ->assertJsonPath(
                'article.url',
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
                "questions.best_article.criteria.article_{$article->id}.title",
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
                    'best_article' => [
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

        $this->postJson(route('blog.search', ['locale' => 'es']), [
            'query' => '¿Qué pedales usó Hendrix en 1969?',
        ])
            ->assertOk()
            ->assertExactJson(['matched' => false]);
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
                    'best_article' => [
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

        $this->postJson(route('blog.search', ['locale' => 'es']), [
            'query' => '¿Cómo mejoro?',
        ])
            ->assertOk()
            ->assertExactJson(['matched' => false]);
    }

    public function test_it_uses_a_conservative_lexical_fallback_without_an_api_key(): void
    {
        config()->set('services.typesafe.key', null);

        $article = $this->createArticle(
            title: 'Cómo practicar con metrónomo sin frustrarte',
            slug: 'como-practicar-con-metronomo',
            excerpt: 'Elige una velocidad útil sin sacrificar precisión.',
        );

        $this->postJson(route('blog.search', ['locale' => 'es']), [
            'query' => '¿Cómo puedo practicar con metrónomo?',
        ])
            ->assertOk()
            ->assertJsonPath('matched', true)
            ->assertJsonPath('article.title', $article->title);

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

        $this->postJson(route('blog.search', ['locale' => 'es']), [
            'query' => '¿Cómo practicar con metrónomo?',
        ])
            ->assertOk()
            ->assertExactJson(['matched' => false]);
    }

    private function createArticle(
        string $title,
        string $slug,
        string $excerpt,
        bool $published = true,
    ): PostTranslation {
        $post = Post::query()->create([
            'user_id' => User::factory()->create()->id,
        ]);

        return $post->translations()->create([
            'locale' => 'es',
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
}
