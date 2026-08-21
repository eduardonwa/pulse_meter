<?php

namespace Tests\Feature;

use App\Models\RoutineTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaveRoutineTemplateAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_free_user_cannot_save_template_copy_to_server(): void
    {
        $user = User::factory()->create();

        $template = RoutineTemplate::create([
            'user_id' => $user->id,
            'difficulty' => 'beginner',
        ]);

        $response = $this
            ->actingAs($user)
            ->postJson(
                route('routines.save-copy', [
                    'routineTemplate' => $template->id,
                ]),
                [
                    'name' => 'Copied Routine',

                    'steps' => [
                        [
                            'name' => 'Alternate Picking',
                            'bpm' => 160,
                            'mode' => 'timer',
                            'duration_seconds' => 60,
                        ],
                    ],
                ]
            );

        $response->assertForbidden();

        $this->assertDatabaseMissing(
            'practice_routines',
            [
                'user_id' => $user->id,
                'routine_template_id' => $template->id,
            ]
        );
    }
}