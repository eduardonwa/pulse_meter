<?php

namespace Tests\Feature\Practice;

use App\Models\PracticeRoutine;
use App\Models\RoutineTemplate;
use App\Models\TrialEntitlement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SaveRoutineTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_pro_user_can_save_template_copy(): void
    {
        $user = User::factory()->create([
            'plan' => 'pro',
        ]);

        $template = $this->createTemplate();

        $response = $this
            ->actingAs($user)
            ->postJson(
                route('routines.save-copy', [
                    'routineTemplate' => $template,
                ]),
                $this->templatePayload()
            );

        $response->assertSuccessful();

        $routine = PracticeRoutine::query()
            ->where('user_id', $user->id)
            ->where(
                'routine_template_id',
                $template->id
            )
            ->firstOrFail();

        /*
         * Ownership + provenance.
         */
        $this->assertSame(
            $user->id,
            $routine->user_id
        );

        $this->assertSame(
            $template->id,
            $routine->routine_template_id
        );

        $this->assertSame(
            'Alternate Picking Builder',
            $routine->name
        );

        /*
         * Los ejercicios también fueron copiados.
         */
        $this->assertDatabaseHas(
            'practice_routine_steps',
            [
                'practice_routine_id' =>
                    $routine->id,

                'name' =>
                    '2 NPS alternate picking',

                'bpm' => 90,
                'mode' => 'timer',
                'duration_seconds' => 300,
                'position' => 0,
            ]
        );

        $this->assertDatabaseHas(
            'practice_routine_steps',
            [
                'practice_routine_id' =>
                    $routine->id,

                'name' =>
                    'Downpicking',

                'bpm' => 120,
                'mode' => 'classic',
                'duration_seconds' => null,
                'position' => 1,
            ]
        );
    }

    public function test_trial_user_cannot_save_template_copy(): void
    {
        $user = User::factory()->create();

        /*
         * Trial realmente activo.
         *
         * Tiene acceso Pro temporal para usar features,
         * pero NO paid Pro access.
         */
        $this->createActiveTrial($user);

        $template = $this->createTemplate();

        $response = $this
            ->actingAs($user)
            ->postJson(
                route('routines.save-copy', [
                    'routineTemplate' => $template,
                ]),
                $this->templatePayload()
            );

        $response->assertForbidden();

        $this->assertDatabaseMissing(
            'practice_routines',
            [
                'user_id' => $user->id,
                'routine_template_id' =>
                    $template->id,
            ]
        );
    }

    public function test_free_user_cannot_save_template_copy(): void
    {
        $user = User::factory()->create([
            'plan' => 'free',
        ]);

        $template = $this->createTemplate();

        $response = $this
            ->actingAs($user)
            ->postJson(
                route('routines.save-copy', [
                    'routineTemplate' => $template,
                ]),
                $this->templatePayload()
            );

        $response->assertForbidden();

        $this->assertDatabaseMissing(
            'practice_routines',
            [
                'user_id' => $user->id,
                'routine_template_id' =>
                    $template->id,
            ]
        );
    }

    public function test_user_cannot_save_same_template_twice(): void
    {
        $user = User::factory()->create([
            'plan' => 'pro',
        ]);

        $template = $this->createTemplate();

        $url = route('routines.save-copy', [
            'routineTemplate' => $template,
        ]);

        $payload = $this->templatePayload();

        $this
            ->actingAs($user)
            ->postJson($url, $payload)
            ->assertSuccessful();

        /*
         * La segunda debe ser rechazada.
         *
         * 409 = el recurso ya existe para este usuario.
         */
        $this
            ->actingAs($user)
            ->postJson($url, $payload)
            ->assertStatus(409);

        $this->assertSame(
            1,
            PracticeRoutine::query()
                ->where('user_id', $user->id)
                ->where(
                    'routine_template_id',
                    $template->id
                )
                ->count()
        );
    }

    private function templatePayload(): array
    {
        return [
            'name' => 'Alternate Picking Builder',

            'steps' => [
                [
                    'name' =>
                        '2 NPS alternate picking',

                    'bpm' => 90,
                    'mode' => 'timer',
                    'duration_seconds' => 300,
                ],

                [
                    'name' => 'Downpicking',
                    'bpm' => 120,
                    'mode' => 'classic',
                    'duration_seconds' => null,
                ],
            ],
        ];
    }

    private function createTemplate(): RoutineTemplate
    {
        $owner = User::factory()->create();

        $template = new RoutineTemplate();

        $template->user_id = $owner->id;
        $template->instrument = 'guitar';
        $template->difficulty = 'intermediate';

        $template->save();

        return $template;
    }

    private function createActiveTrial(
        User $user
    ): TrialEntitlement {
        $trial = new TrialEntitlement();

        $trial->status = 'active';
        $trial->granted_seconds = 3600;
        $trial->used_seconds = 0;
        $trial->started_at = now();
        $trial->expires_at = now()->addDays(15);

        $trial->active_session_id =
            (string) Str::uuid();

        $trial->last_heartbeat_at =
            now()->subSeconds(5);

        $trial->paused_at = null;
        $trial->pause_reason = null;
        $trial->completed_at = null;

        $user->trialEntitlement()->save($trial);

        return $trial;
    }
}