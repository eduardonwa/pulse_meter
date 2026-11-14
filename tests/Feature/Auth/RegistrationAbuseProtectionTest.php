<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class RegistrationAbuseProtectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear('registration:127.0.0.1');
    }

    public function test_registration_page_has_a_honeypot_field(): void
    {
        $this->get('/register')->assertOk()->assertSee('name="website"', false);
    }

    public function test_filled_honeypot_rejects_registration(): void
    {
        $this->post('/register', [
            'name' => 'Spam account',
            'email' => 'spam@example.com',
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!',
            'website' => 'https://spam.example.com',
        ])->assertSessionHasErrors('website');

        $this->assertDatabaseMissing('users', ['email' => 'spam@example.com']);
    }

    public function test_registration_is_limited_by_ip_even_for_invalid_submissions(): void
    {
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post('/register', [
                'name' => 'Invalid',
                'email' => 'invalid'.$attempt.'@example.com',
                'password' => '',
            ])->assertSessionHasErrors('password');
        }

        $this->post('/register', [
            'name' => 'Legitimate',
            'email' => 'legitimate@example.com',
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!',
            'website' => '',
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('users', ['email' => 'legitimate@example.com']);
    }
}
