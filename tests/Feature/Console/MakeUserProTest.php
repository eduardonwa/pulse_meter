<?php

namespace Tests\Feature\Console;

use App\Models\TrialEntitlement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MakeUserProTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_upgrades_the_user_and_converts_an_active_trial(): void
    {
        $user = User::factory()->create([
            'plan' => 'free',
        ]);

        $trial = new TrialEntitlement();

        $trial->forceFill([
            'status' => 'active',
            'granted_seconds' => 3600,
            'used_seconds' => 49,
            'started_at' => now()->subMinute(),
            'expires_at' => now()->addDays(30),
            'active_session_id' => (string) Str::uuid(),
            'last_heartbeat_at' => now(),
        ]);

        $trial->user()->associate($user);
        $trial->save();

        $this->artisan('user:make-pro', [
            'email' => $user->email,
        ])
            ->expectsOutput("{$user->email} is now Pro.")
            ->assertExitCode(0);

        $user->refresh();
        $trial->refresh();

        $this->assertSame('pro', $user->plan);
        $this->assertSame('converted', $trial->status);
        $this->assertNotNull($trial->converted_at);
        $this->assertNull($trial->active_session_id);
    }
}