<?php

namespace Tests\Feature\Plans;

use App\Models\TrialEntitlement;
use App\Models\User;
use App\Services\Plans\DowngradeToFree;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DowngradeToFreeTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_downgrades_a_pro_user_to_free(): void
    {
        $user = User::factory()->create([
            'plan' => 'pro',
        ]);

        app(DowngradeToFree::class)->downgrade($user);

        $this->assertSame(
            'free',
            $user->refresh()->plan
        );

        $this->assertFalse($user->isPro());
        $this->assertFalse($user->hasProAccess());
    }

    public function test_it_does_not_restore_a_converted_trial(): void
    {
        $convertedAt = Carbon::parse(
            '2026-08-05 12:00:00'
        );

        $user = User::factory()->create([
            'plan' => 'pro',
        ]);

        $trial = new TrialEntitlement();

        $trial->forceFill([
            'status' => 'converted',
            'granted_seconds' => 3600,
            'used_seconds' => 49,
            'started_at' => now()->subDay(),
            'expires_at' => now()->addDays(29),
            'converted_at' => $convertedAt,
        ]);

        $trial->user()->associate($user);
        $trial->save();

        app(DowngradeToFree::class)->downgrade($user);

        $user->refresh();
        $trial->refresh();

        $this->assertSame('free', $user->plan);
        $this->assertFalse($user->hasProAccess());

        $this->assertSame(
            'converted',
            $trial->status
        );

        $this->assertTrue(
            $trial->converted_at->equalTo(
                $convertedAt
            )
        );
    }

    public function test_downgrading_an_already_free_user_is_idempotent(): void
    {
        $user = User::factory()->create([
            'plan' => 'free',
        ]);

        app(DowngradeToFree::class)->downgrade($user);
        app(DowngradeToFree::class)->downgrade($user);

        $this->assertSame(
            'free',
            $user->refresh()->plan
        );
    }
}