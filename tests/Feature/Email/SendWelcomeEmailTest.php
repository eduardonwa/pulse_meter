<?php

namespace Tests\Feature\Email;

use App\Listeners\SendWelcomeEmail;
use App\Mail\WelcomeNewUser;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendWelcomeEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sends_one_welcome_email_to_verified_user(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $listener = new SendWelcomeEmail();
        $listener->handle(new Verified($user));

        Mail::assertSent(
            WelcomeNewUser::class,
            function (WelcomeNewUser $mail) use ($user) {
                return $mail->hasTo($user->email);
            }
        );

        Mail::assertSentCount(1);
    }

    public function test_welcome_email_contains_user_name_and_content(): void
    {
        $user = User::factory()->create([
            'name' => 'Eduardo',
        ]);

        $mail = new WelcomeNewUser($user);

        $mail->assertHasSubject('Welcome to Dorelog');
        $mail->assertSeeInHtml('Eduardo');
        $mail->assertSeeInHtml('Thanks for signing up for Dorelog');
        $mail->assertSeeInHtml('Back to practice');
    }
}