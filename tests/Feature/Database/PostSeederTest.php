<?php

namespace Tests\Feature\Database;

use App\Models\Post;
use App\Models\PostTranslation;
use Database\Seeders\PostSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PostSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_posts_with_translations_and_thumbnails(): void
    {
        Storage::fake('public');

        $this->seed(PostSeeder::class);

        $this->assertDatabaseCount('posts', 10);
        $this->assertDatabaseCount('post_translations', 20);

        Post::query()
            ->with('translations')
            ->each(function (Post $post): void {
                $this->assertNotNull($post->thumbnail);

                Storage::disk('public')->assertExists(
                    $post->thumbnail
                );

                $this->assertCount(2, $post->translations);

                $this->assertEqualsCanonicalizing(
                    ['es', 'en'],
                    $post->translations
                        ->pluck('locale')
                        ->all(),
                );

                $dimensions = getimagesize(
                    Storage::disk('public')->path(
                        $post->thumbnail
                    )
                );

                $this->assertNotFalse($dimensions);
                $this->assertSame(1600, $dimensions[0]);
                $this->assertSame(900, $dimensions[1]);
                $this->assertSame(
                    'image/webp',
                    $dimensions['mime'],
                );
            });
    }

    public function test_every_translation_contains_published_content(): void
    {
        Storage::fake('public');

        $this->seed(PostSeeder::class);

        PostTranslation::query()
            ->each(function (PostTranslation $translation): void {
                $this->assertNotEmpty($translation->title);
                $this->assertNotEmpty($translation->slug);
                $this->assertNotEmpty($translation->excerpt);
                $this->assertNotEmpty($translation->body);
                $this->assertNotEmpty($translation->meta_title);
                $this->assertNotEmpty(
                    $translation->meta_description
                );

                $this->assertNotNull(
                    $translation->published_at
                );

                $this->assertContains(
                    $translation->locale,
                    ['es', 'en'],
                );

                $this->assertSame(
                    'doc',
                    $translation->body['type'],
                );

                $this->assertNotEmpty(
                    $translation->body['content'],
                );
            });
    }
}