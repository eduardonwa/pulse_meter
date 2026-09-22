<?php

namespace Tests\Feature\Blog;

use App\Mail\NewContentQuestion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContentQuestionTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_reader_can_request_a_personal_answer(): void
    {
        Mail::fake();
        config()->set('mail.support_address', 'support@dorelog.com');

        $this->postJson(route('chat.questions.store'), [
            'locale' => 'es',
            'question' => '¿Cómo puedo practicar gallops sin tensarme?',
            'name' => 'Ana',
            'email' => 'ana@example.com',
            'website' => '',
        ])
            ->assertCreated()
            ->assertExactJson(['submitted' => true]);

        $this->assertDatabaseHas('content_questions', [
            'locale' => 'es',
            'question' => '¿Cómo puedo practicar gallops sin tensarme?',
            'name' => 'Ana',
            'email' => 'ana@example.com',
            'status' => 'pending',
        ]);

        Mail::assertSent(
            NewContentQuestion::class,
            function (NewContentQuestion $mail): bool {
                $replyTo = $mail->envelope()->replyTo[0];

                return $mail->hasTo('support@dorelog.com')
                    && $replyTo->address === 'ana@example.com'
                    && $replyTo->name === 'Ana';
            },
        );
    }

    public function test_email_and_question_are_required(): void
    {
        Mail::fake();

        $this->postJson(route('chat.questions.store'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['locale', 'question', 'email']);

        $this->assertDatabaseCount('content_questions', 0);
        Mail::assertNothingSent();
    }

    public function test_the_honeypot_rejects_bot_submissions(): void
    {
        Mail::fake();

        $this->postJson(route('chat.questions.store'), [
            'locale' => 'en',
            'question' => 'A plausible question from a bot',
            'email' => 'bot@example.com',
            'website' => 'https://spam.example.com',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('website');

        $this->assertDatabaseCount('content_questions', 0);
        Mail::assertNothingSent();
    }
}
