<?php

namespace Tests\Feature\Practice;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PracticeContextLimitsTest extends TestCase
{
    use RefreshDatabase;

    private function createProUser(): User
    {
        /** @var User $user */
        $user = User::factory()->create([
                'plan' => 'pro',
        ]);
        
        return $user;
    }

    public function test_pro_practice_context_exposes_exercise_limits(): void
    {
        $user = $this->createProUser();

        $response = $this
            ->actingAs($user)
            ->get(route('welcome'));

        $response->assertOk();

        $response->assertViewHas(
            'practiceContext',
            function (array $practiceContext): bool {
                return (
                    $practiceContext['limits'] ?? null
                ) === [
                    'exercise_count' => 20,
                    'exercise_duration_seconds' => 900,
                ];
            }
        );
    }
}