<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Socialite\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class GoogleAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_is_redirected_to_google(): void
    {
        Socialite::fake('google');

        $response = $this->get('/auth/google');

        $response->assertRedirect();
    }

    public function test_new_google_user_is_created_verified_and_authenticated(): void
    {
        Event::fake([
            Registered::class,
            Verified::class,
        ]);

        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-123',
            'name' => 'Eduardo Coello',
            'email' => 'eduardo@example.com',
        ]));

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect('/');

        $user = User::where('email', 'eduardo@example.com')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertSame('google-123', $user->google_id);
        $this->assertNotNull($user->email_verified_at);

        Event::assertDispatched(
            Registered::class,
            fn (Registered $event) => $event->user->is($user)
        );

        Event::assertDispatched(
            Verified::class,
            fn (Verified $event) => $event->user->is($user)
        );
    }

    public function test_existing_unverified_user_is_linked_verified_and_authenticated(): void
    {
        Event::fake([
            Registered::class,
            Verified::class,
        ]);

        $user = User::factory()->create([
            'email' => 'eduardo@example.com',
            'email_verified_at' => null,
            'google_id' => null,
        ]);

        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-123',
            'name' => 'Eduardo Coello',
            'email' => 'eduardo@example.com',
        ]));

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect('/');

        $user->refresh();

        $this->assertAuthenticatedAs($user);
        $this->assertSame('google-123', $user->google_id);
        $this->assertNotNull($user->email_verified_at);
        $this->assertSame(1, User::where('email', $user->email)->count());

        Event::assertNotDispatched(Registered::class);

        Event::assertDispatched(
            Verified::class,
            fn (Verified $event) => $event->user->is($user)
        );
    }

    public function test_existing_verified_user_is_not_registered_or_verified_again(): void
    {
        Event::fake([
            Registered::class,
            Verified::class,
        ]);

        $user = User::factory()->create([
            'email' => 'eduardo@example.com',
            'email_verified_at' => now(),
            'google_id' => null,
        ]);

        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-123',
            'name' => 'Eduardo Coello',
            'email' => 'eduardo@example.com',
        ]));

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect('/');

        $user->refresh();

        $this->assertAuthenticatedAs($user);
        $this->assertSame('google-123', $user->google_id);
        $this->assertSame(1, User::where('email', $user->email)->count());

        Event::assertNotDispatched(Registered::class);
        Event::assertNotDispatched(Verified::class);
    }

    public function test_returning_google_user_logs_into_the_same_account(): void
    {
        Event::fake([
            Registered::class,
            Verified::class,
        ]);

        $user = User::factory()->create([
            'email' => 'eduardo@example.com',
            'email_verified_at' => now(),
            'google_id' => 'google-123',
        ]);

        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-123',
            'name' => 'Eduardo Coello',
            'email' => 'eduardo@example.com',
        ]));

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
        $this->assertSame(1, User::where('google_id', 'google-123')->count());

        Event::assertNotDispatched(Registered::class);
        Event::assertNotDispatched(Verified::class);
    }
}