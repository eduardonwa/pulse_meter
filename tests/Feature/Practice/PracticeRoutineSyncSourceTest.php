<?php

namespace Tests\Feature\Practice;

use App\Models\PracticeRoutine;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PracticeRoutineSyncSourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_have_many_normal_routines(): void
    {
        $user = $this->createUser();

        $user->practiceRoutines()->create([
            'name' => 'Routine 1',
            'position' => 0,
            'is_default' => true,
            'sync_source' => null,
        ]);

        $user->practiceRoutines()->create([
            'name' => 'Routine 2',
            'position' => 1,
            'is_default' => false,
            'sync_source' => null,
        ]);

        $this->assertSame(
            2,
            $user->practiceRoutines()->count()
        );
    }

    public function test_a_user_can_only_have_one_free_local_routine(): void
    {
        $user = $this->createUser();

        $user->practiceRoutines()->create([
            'name' => 'Free Exercises',
            'position' => 0,
            'is_default' => true,

            'sync_source' =>
                PracticeRoutine::SYNC_SOURCE_FREE_LOCAL,
        ]);

        $this->expectException(
            QueryException::class
        );

        $user->practiceRoutines()->create([
            'name' => 'Another Free Routine',
            'position' => 1,
            'is_default' => false,

            'sync_source' =>
                PracticeRoutine::SYNC_SOURCE_FREE_LOCAL,
        ]);
    }

    private function createUser(): User
    {
        /** @var User $user */
        $user = User::factory()->create();

        return $user;
    }
}