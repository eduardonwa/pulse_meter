<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\User;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Geometry\Factories\CircleFactory;
use Intervention\Image\Laravel\Facades\Image;
use RuntimeException;

class PostSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException(
                'PostSeeder must not run in production.'
            );
        }

        $faker = Faker::create();
        
        $user = User::query()->first() ?? User::factory()->create();

        $posts = [
            [
                'es' => [
                    'title' => 'Cómo construir una rutina de práctica musical',
                    'excerpt' => 'Una forma sencilla de organizar tus ejercicios sin convertir la práctica en una obligación.',
                ],
                'en' => [
                    'title' => 'How to build a music practice routine',
                    'excerpt' => 'A simple way to organize your exercises without turning practice into a chore.',
                ],
            ],
            [
                'es' => [
                    'title' => 'Cuánto tiempo practicar guitarra cada día',
                    'excerpt' => 'La duración importa menos que la intención y la consistencia de cada sesión.',
                ],
                'en' => [
                    'title' => 'How long to practice guitar every day',
                    'excerpt' => 'Duration matters less than the intention and consistency behind each session.',
                ],
            ],
            [
                'es' => [
                    'title' => 'Cómo practicar con metrónomo sin frustrarte',
                    'excerpt' => 'Aprende a elegir una velocidad útil y a progresar sin sacrificar precisión.',
                ],
                'en' => [
                    'title' => 'How to practice with a metronome without frustration',
                    'excerpt' => 'Learn how to choose a useful tempo and progress without sacrificing accuracy.',
                ],
            ],
            [
                'es' => [
                    'title' => 'Qué hacer cuando tu práctica se siente estancada',
                    'excerpt' => 'Algunas señales para identificar el problema y ajustar tu rutina.',
                ],
                'en' => [
                    'title' => 'What to do when your practice feels stuck',
                    'excerpt' => 'A few signs that can help you identify the problem and adjust your routine.',
                ],
            ],
            [
                'es' => [
                    'title' => 'Ejercicios técnicos y canciones: cómo equilibrarlos',
                    'excerpt' => 'Una sesión útil puede desarrollar tu técnica sin desconectarla de la música.',
                ],
                'en' => [
                    'title' => 'Technical exercises and songs: finding the balance',
                    'excerpt' => 'A useful session can develop technique without separating it from music.',
                ],
            ],
            [
                'es' => [
                    'title' => 'Cómo registrar tu progreso musical',
                    'excerpt' => 'Registrar lo que practicas ayuda a tomar mejores decisiones para la siguiente sesión.',
                ],
                'en' => [
                    'title' => 'How to track your musical progress',
                    'excerpt' => 'Tracking your practice helps you make better decisions for your next session.',
                ],
            ],
            [
                'es' => [
                    'title' => 'Por qué repetir un ejercicio no siempre funciona',
                    'excerpt' => 'La repetición solamente ayuda cuando prestas atención a lo que estás intentando mejorar.',
                ],
                'en' => [
                    'title' => 'Why repeating an exercise does not always work',
                    'excerpt' => 'Repetition only helps when you pay attention to what you are trying to improve.',
                ],
            ],
            [
                'es' => [
                    'title' => 'Cómo dividir una hora de práctica',
                    'excerpt' => 'Una propuesta flexible para combinar técnica, repertorio y exploración musical.',
                ],
                'en' => [
                    'title' => 'How to divide one hour of practice',
                    'excerpt' => 'A flexible approach to combining technique, repertoire, and musical exploration.',
                ],
            ],
            [
                'es' => [
                    'title' => 'Practicar lento no significa practicar fácil',
                    'excerpt' => 'Reducir la velocidad permite observar detalles que desaparecen cuando tocas demasiado rápido.',
                ],
                'en' => [
                    'title' => 'Practicing slowly does not mean practicing easily',
                    'excerpt' => 'Reducing the tempo lets you notice details that disappear when you play too quickly.',
                ],
            ],
            [
                'es' => [
                    'title' => 'Cómo elegir qué practicar hoy',
                    'excerpt' => 'Una pequeña evaluación puede ayudarte a elegir ejercicios con más intención.',
                ],
                'en' => [
                    'title' => 'How to decide what to practice today',
                    'excerpt' => 'A short assessment can help you choose exercises with greater intention.',
                ],
            ],
        ];

        foreach ($posts as $index => $content) {
            $thumbnail = $this->createThumbnail(
                background: $faker->hexColor(),
                accent: $faker->hexColor(),
            );

            $post = Post::query()->create([
                'user_id' => $user->id,
                'thumbnail' => $thumbnail,
            ]);

            foreach (['es', 'en'] as $locale) {
                $translation = $content[$locale];

                $post->translations()->create([
                    'locale' => $locale,
                    'title' => $translation['title'],
                    'slug' => Str::slug($translation['title']),
                    'excerpt' => $translation['excerpt'],
                    'body' => $this->body(
                        locale: $locale,
                        title: $translation['title'],
                    ),
                    'meta_title' => $translation['title'],
                    'meta_description' => $translation['excerpt'],
                    'thumbnail_alt' => $translation['title'],
                    'published_at' => now()->subDays(9 - $index),
                ]);
            }
        }
    }

    private function body(string $locale, string $title): array
    {
        $isSpanish = $locale === 'es';

        return [
            'type' => 'doc',
            'content' => [
                $this->heading(
                    $isSpanish
                        ? 'Identifica lo que quieres mejorar'
                        : 'Identify what you want to improve',
                ),
                $this->paragraph(
                    $isSpanish
                        ? "Antes de comenzar, piensa en una parte concreta de {$title} que quieras trabajar. Una meta pequeña hace que sea más fácil evaluar tu sesión."
                        : "Before you begin, choose one specific part of {$title} that you want to work on. A small goal makes your session easier to evaluate.",
                ),
                $this->paragraph(
                    $isSpanish
                        ? 'No necesitas corregir todo durante la misma sesión. Escucha con atención y elige un problema que puedas observar claramente.'
                        : 'You do not need to correct everything during the same session. Listen carefully and choose one problem that you can observe clearly.',
                ),
                $this->heading(
                    $isSpanish
                        ? 'Trabaja en sesiones pequeñas'
                        : 'Work in short sessions',
                ),
                $this->paragraph(
                    $isSpanish
                        ? 'Dedica algunos minutos al ejercicio, descansa brevemente y vuelve a intentarlo. Esto evita que la repetición se vuelva automática.'
                        : 'Spend a few minutes on the exercise, take a short break, and try again. This keeps repetition from becoming automatic.',
                ),
                $this->heading(
                    $isSpanish
                        ? 'Revisa lo que ocurrió'
                        : 'Review what happened',
                ),
                $this->paragraph(
                    $isSpanish
                        ? 'Al terminar, registra qué funcionó, qué resultó difícil y qué te gustaría intentar durante la siguiente práctica.'
                        : 'When you finish, record what worked, what felt difficult, and what you would like to try during your next practice.',
                ),
            ],
        ];
    }

    private function heading(string $text): array
    {
        return [
            'type' => 'heading',
            'attrs' => [
                'level' => 2,
                'textAlign' => 'start',
            ],
            'content' => [
                [
                    'type' => 'text',
                    'text' => $text,
                ],
            ],
        ];
    }

    private function paragraph(string $text): array
    {
        return [
            'type' => 'paragraph',
            'attrs' => [
                'textAlign' => 'start',
            ],
            'content' => [
                [
                    'type' => 'text',
                    'text' => $text,
                ],
            ],
        ];
    }

    private function createThumbnail(
        string $background,
        string $accent,
    ): string {
        $path = 'blog/thumbs/' . Str::uuid() . '.webp';

        $image = Image::createImage(1600, 900)->fill($background);

        $image->drawCircle(
            function (CircleFactory $circle) use ($accent): void {
                $circle->at(1320, 120);
                $circle->diameter(850);
                $circle->background($accent);
            }
        );

        $image->drawCircle(
            function (CircleFactory $circle): void {
                $circle->at(1250, 800);
                $circle->diameter(600);
                $circle->background('rgba(0, 0, 0, 0.15)');
            }
        );

        $image = $image->encodeUsingFileExtension('webp',quality: 82);

        $stored = Storage::disk('public')->put($path, (string) $image, 'public');

        if (! $stored) {
            throw new \RuntimeException(
                "Could not store seeded thumbnail: {$path}"
            );
        }

        return $path;
    }
}