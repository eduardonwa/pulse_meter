<?php

namespace Tests\Feature\Practice;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FreeExerciseImportPreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_disables_the_prompt_for_the_account(): void
    {
        $user = $this->createUser([
            'plan' => 'pro',
        ]);

        $this
            ->actingAs($user)
            ->patchJson(
                '/practice-settings/free-exercise-import-prompt',
                [
                    'enabled' => false,
                ],
            )
            ->assertOk()
            ->assertJsonPath(
                'ask_before_importing_free_exercises',
                false,
            );

        $this->assertFalse(
            $user
                ->fresh()
                ->ask_before_importing_free_exercises,
        );
    }

    public function test_it_enables_the_prompt_for_the_account(): void
    {
        $user = $this->createUser([
            'plan' => 'pro',
            'ask_before_importing_free_exercises' => false,
        ]);

        $this
            ->actingAs($user)
            ->patchJson(
                '/practice-settings/free-exercise-import-prompt',
                [
                    'enabled' => true,
                ],
            )
            ->assertOk();

        $this->assertTrue(
            $user
                ->fresh()
                ->ask_before_importing_free_exercises,
        );
    }
}
