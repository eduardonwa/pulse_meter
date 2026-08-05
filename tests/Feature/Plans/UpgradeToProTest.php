<?php

namespace Tests\Feature\Plans;

use App\Models\TrialEntitlement;
use App\Models\User;
use App\Services\Plans\UpgradeToPro;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;
use Illuminate\Support\Carbon;

class UpgradeToProTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_converts_an_active_trial_when_upgrading_to_pro(): void
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

        app(UpgradeToPro::class)->upgrade($user);

        $user->refresh();
        $trial->refresh();

        $this->assertSame('pro', $user->plan);

        $this->assertSame(
            'converted',
            $trial->status
        );

        $this->assertSame(
            49,
            $trial->used_seconds
        );

        $this->assertNull(
            $trial->active_session_id
        );

        $this->assertNull(
            $trial->paused_at
        );

        $this->assertNull(
            $trial->pause_reason
        );

        $this->assertNotNull(
            $trial->converted_at
        );
    }

    public function test_it_converts_a_paused_trial_when_upgrading_to_pro(): void
    {
        $user = User::factory()->create([
            'plan' => 'free',
        ]);

        $trial = new TrialEntitlement();

        $trial->forceFill([
            'status' => 'paused',
            'granted_seconds' => 3600,
            'used_seconds' => 49,
            'started_at' => now()->subMinute(),
            'expires_at' => now()->addDays(30),
            'paused_at' => now(),
            'pause_reason' => 'manual',
            'active_session_id' => null,
            'last_heartbeat_at' => now(),
        ]);

        $trial->user()->associate($user);
        $trial->save();

        app(UpgradeToPro::class)->upgrade($user);

        $user->refresh();
        $trial->refresh();

        $this->assertSame('pro', $user->plan);
        $this->assertSame('converted', $trial->status);

        $this->assertSame(
            49,
            $trial->used_seconds
        );

        $this->assertNull(
            $trial->paused_at
        );

        $this->assertNull(
            $trial->pause_reason
        );

        $this->assertInstanceOf(
            Carbon::class,
            $trial->converted_at
        );
    }

    public function test_it_preserves_a_terminal_trial_status_when_upgrading_to_pro(): void
    {
        foreach (['completed', 'expired', 'converted'] as $status) {
            $user = User::factory()->create([
                'plan' => 'free',
            ]);

            $originalConvertedAt = $status === 'converted'
                ? now()->subDay()->startOfSecond()
                : null;

            $trial = new TrialEntitlement();

            $trial->forceFill([
                'status' => $status,
                'granted_seconds' => 3600,
                'used_seconds' => $status === 'completed'
                    ? 3600
                    : 49,
                'started_at' => now()->subDays(2),
                'expires_at' => now()->addDays(30),
                'completed_at' => $status === 'completed'
                    ? now()->subDay()
                    : null,
                'converted_at' => $originalConvertedAt,
            ]);

            $trial->user()->associate($user);
            $trial->save();

            app(UpgradeToPro::class)->upgrade($user);

            $user->refresh();
            $trial->refresh();

            $this->assertSame(
                'pro',
                $user->plan,
                "User was not upgraded for status [{$status}]."
            );

            $this->assertSame(
                $status,
                $trial->status,
                "Trial status [{$status}] was unexpectedly rewritten."
            );

            if ($status === 'converted') {
                $this->assertTrue(
                    $trial->converted_at->equalTo(
                        $originalConvertedAt
                    )
                );
            }
        }
    }
}