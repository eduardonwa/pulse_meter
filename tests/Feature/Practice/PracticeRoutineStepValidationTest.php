<?php

namespace Tests\Feature\Practice;

use App\Models\TrialEntitlement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PracticeRoutineStepValidationTest extends TestCase
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

    private function createActiveTrialUser(): User
    {
        $user = User::factory()->create([
            'plan' => 'free',
        ]);

        $trial = new TrialEntitlement;

        $trial->forceFill([
            'status' => 'active',
            'granted_seconds' => 3600,
            'used_seconds' => 0,
            'started_at' => now(),
            'expires_at' => now()->addDays(15),
        ]);

        $trial->user()->associate($user);
        $trial->save();

        return $user;
    }

    public function test_trial_timer_exercise_requires_a_duration_when_created(): void
    {
        $user = $this->createActiveTrialUser();

        $routine = $user->practiceRoutines()->create([
            'name' => 'Routine 1',
            'position' => 0,
            'is_default' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->postJson(
                route(
                    'practice-routine-steps.store',
                    $routine
                ),
                [
                    'name' => 'Timer without duration',
                    'bpm' => 120,
                    'mode' => 'timer',
                    'duration_seconds' => null,
                ]
            );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'duration_seconds',
            ]);

        $this->assertDatabaseMissing(
            'practice_routine_steps',
            [
                'practice_routine_id' => $routine->id,
                'name' => 'Timer without duration',
            ]
        );
    }

    public function test_pro_can_create_a_timer_exercise_of_up_to_fifteen_minutes(): void
    {
        $user = $this->createProUser();

        $routine = $user->practiceRoutines()->create([
            'name' => 'Routine 1',
            'position' => 0,
            'is_default' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->postJson(
                route(
                    'practice-routine-steps.store',
                    $routine
                ),
                [
                    'name' => 'Long Timer',
                    'bpm' => 120,
                    'mode' => 'timer',
                    'duration_seconds' => 900,
                ]
            );

        $response->assertCreated();

        $this->assertDatabaseHas(
            'practice_routine_steps',
            [
                'practice_routine_id' => $routine->id,
                'name' => 'Long Timer',
                'duration_seconds' => 900,
            ]
        );
    }

    public function test_pro_can_update_a_timer_exercise_to_fifteen_minutes(): void
    {
        $user = $this->createProUser();

        $routine = $user->practiceRoutines()->create([
            'name' => 'Routine 1',
            'position' => 0,
            'is_default' => true,
        ]);

        $exercise = $routine->steps()->create([
            'name' => 'Short Timer',
            'bpm' => 120,
            'mode' => 'timer',
            'duration_seconds' => 300,
            'position' => 0,
            'origin' => 'custom',
        ]);

        $response = $this
            ->actingAs($user)
            ->patchJson(
                route(
                    'practice-routine-steps.update',
                    $exercise
                ),
                [
                    'name' => 'Long Timer',
                    'bpm' => 120,
                    'mode' => 'timer',
                    'duration_seconds' => 900,
                ]
            );

        $response->assertOk();

        $this->assertDatabaseHas(
            'practice_routine_steps',
            [
                'id' => $exercise->id,
                'name' => 'Long Timer',
                'duration_seconds' => 900,
            ]
        );
    }

    public function test_trial_cannot_update_a_timer_exercise_beyond_five_minutes(): void
    {
        $user = $this->createActiveTrialUser();

        $routine = $user->practiceRoutines()->create([
            'name' => 'Routine 1',
            'position' => 0,
            'is_default' => true,
        ]);

        $exercise = $routine->steps()->create([
            'name' => 'Timer',
            'bpm' => 120,
            'mode' => 'timer',
            'duration_seconds' => 300,
            'position' => 0,
            'origin' => 'custom',
        ]);

        $response = $this
            ->actingAs($user)
            ->patchJson(
                route('practice-routine-steps.update', $exercise),
                [
                    'name' => 'Too Long Timer',
                    'bpm' => 120,
                    'mode' => 'timer',
                    'duration_seconds' => 301,
                ]
            );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'duration_seconds',
            ]);

        $this->assertDatabaseHas(
            'practice_routine_steps',
            [
                'id' => $exercise->id,
                'duration_seconds' => 300,
            ]
        );
    }

    public function test_pro_cannot_create_a_timer_exercise_beyond_fifteen_minutes(): void
    {
        $user = $this->createProUser();

        $routine = $user->practiceRoutines()->create([
            'name' => 'Routine 1',
            'position' => 0,
            'is_default' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->postJson(
                route('practice-routine-steps.store', $routine),
                [
                    'name' => 'Too Long Timer',
                    'bpm' => 120,
                    'mode' => 'timer',
                    'duration_seconds' => 901,
                ]
            );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'duration_seconds',
            ]);

        $this->assertDatabaseMissing(
            'practice_routine_steps',
            [
                'practice_routine_id' => $routine->id,
                'name' => 'Too Long Timer',
            ]
        );
    }

    public function test_pro_can_create_more_than_ten_exercises(): void
    {
        $user = $this->createProUser();

        $routine = $user->practiceRoutines()->create([
            'name' => 'Routine 1',
            'position' => 0,
            'is_default' => true,
        ]);

        foreach (range(0, 9) as $position) {
            $routine->steps()->create([
                'name' => 'Exercise '.($position + 1),
                'bpm' => 120,
                'mode' => 'timer',
                'duration_seconds' => 60,
                'position' => $position,
                'origin' => 'custom',
            ]);
        }

        $response = $this
            ->actingAs($user)
            ->postJson(
                route(
                    'practice-routine-steps.store',
                    $routine
                ),
                [
                    'name' => 'Exercise 11',
                    'bpm' => 120,
                    'mode' => 'timer',
                    'duration_seconds' => 60,
                ]
            );

        $response->assertCreated();

        $this->assertSame(
            11,
            $routine->steps()->count()
        );

        $this->assertDatabaseHas(
            'practice_routine_steps',
            [
                'practice_routine_id' => $routine->id,
                'name' => 'Exercise 11',
                'position' => 10,
            ]
        );
    }

    public function test_trial_cannot_create_more_than_ten_exercises(): void
    {
        $user = $this->createActiveTrialUser();

        $routine = $user->practiceRoutines()->create([
            'name' => 'Routine 1',
            'position' => 0,
            'is_default' => true,
        ]);

        foreach (range(0, 9) as $position) {
            $routine->steps()->create([
                'name' => 'Exercise '.($position + 1),
                'bpm' => 120,
                'mode' => 'timer',
                'duration_seconds' => 60,
                'position' => $position,
                'origin' => 'custom',
            ]);
        }

        $response = $this
            ->actingAs($user)
            ->postJson(
                route('practice-routine-steps.store', $routine),
                [
                    'name' => 'Exercise 11',
                    'bpm' => 120,
                    'mode' => 'timer',
                    'duration_seconds' => 60,
                ]
            );

        $response->assertUnprocessable();

        $this->assertSame(
            10,
            $routine->steps()->count()
        );

        $this->assertDatabaseMissing(
            'practice_routine_steps',
            [
                'practice_routine_id' => $routine->id,
                'name' => 'Exercise 11',
            ]
        );
    }

    public function test_pro_can_import_a_timer_exercise_of_up_to_fifteen_minutes(): void
    {
        $user = $this->createProUser();

        $response = $this
            ->actingAs($user)
            ->postJson(
                route('practice-routines.import-local'),
                [
                    'steps' => [
                        [
                            'name' => 'Long Imported Timer',
                            'bpm' => 120,
                            'mode' => 'timer',
                            'duration_seconds' => 900,
                        ],
                    ],
                ]
            );

        $response->assertOk();

        $this->assertDatabaseHas(
            'practice_routine_steps',
            [
                'name' => 'Long Imported Timer',
                'mode' => 'timer',
                'duration_seconds' => 900,
            ]
        );
    }

    public function test_trial_and_pro_have_the_expected_exercise_duration_limits(): void
    {
        $trialUser =
            $this->createActiveTrialUser();

        $proUser =
            $this->createProUser();

        $this->assertSame(
            300,
            $trialUser->exerciseDurationLimit()
        );

        $this->assertSame(
            900,
            $proUser->exerciseDurationLimit()
        );
    }

    public function test_pro_can_update_an_exercise_to_400_bpm(): void
    {
        $user = $this->createProUser();

        $routine = $user->practiceRoutines()->create([
            'name' => 'Routine 1',
            'position' => 0,
            'is_default' => true,
        ]);

        $exercise = $routine->steps()->create([
            'name' => 'Fast Exercise',
            'bpm' => 300,
            'mode' => 'classic',
            'duration_seconds' => null,
            'position' => 0,
            'origin' => 'custom',
        ]);

        $response = $this
            ->actingAs($user)
            ->patchJson(
                route(
                    'practice-routine-steps.update',
                    $exercise
                ),
                [
                    'name' => 'Fast Exercise',
                    'bpm' => 400,
                    'mode' => 'classic',
                    'duration_seconds' => null,
                ]
            );

        $response->assertOk();

        $this->assertDatabaseHas(
            'practice_routine_steps',
            [
                'id' => $exercise->id,
                'bpm' => 400,
            ]
        );
    }

    public function test_pro_cannot_update_an_exercise_beyond_400_bpm(): void
    {
        $user = $this->createProUser();

        $routine = $user->practiceRoutines()->create([
            'name' => 'Routine 1',
            'position' => 0,
            'is_default' => true,
        ]);

        $exercise = $routine->steps()->create([
            'name' => 'Fast Exercise',
            'bpm' => 400,
            'mode' => 'classic',
            'duration_seconds' => null,
            'position' => 0,
            'origin' => 'custom',
        ]);

        $response = $this
            ->actingAs($user)
            ->patchJson(
                route(
                    'practice-routine-steps.update',
                    $exercise
                ),
                [
                    'name' => 'Too Fast Exercise',
                    'bpm' => 401,
                    'mode' => 'classic',
                    'duration_seconds' => null,
                ]
            );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'bpm',
            ]);

        $this->assertDatabaseHas(
            'practice_routine_steps',
            [
                'id' => $exercise->id,
                'name' => 'Fast Exercise',
                'bpm' => 400,
            ]
        );
    }
}
