<?php

namespace Tests\Feature\Plans;

use App\Livewire\PracticeDialog;
use App\Models\TrialEntitlement;
use App\Models\User;
use App\Services\Practice\CreatePracticePlaylist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PracticeResourceLimitsTest extends TestCase
{
    use RefreshDatabase;

    public function test_trial_cannot_create_a_fourth_routine(): void
    {
        $user = $this->createActiveTrialUser();

        foreach (range(0, 2) as $position) {
            $user->practiceRoutines()->create([
                'name' => 'Routine ' . ($position + 1),
                'position' => $position,
                'is_default' => $position === 0,
            ]);
        }

        $routines = $user->practiceRoutines()
            ->withCount('steps')
            ->orderBy('position')
            ->get()
            ->map(fn ($routine) => [
                'id' => $routine->id,
                'name' => $routine->name,
                'position' => $routine->position,
                'is_default' => (bool) $routine->is_default,
                'steps_count' => $routine->steps_count,
            ])
            ->values()
            ->all();

        $component = Livewire::actingAs($user)
            ->test(PracticeDialog::class, [
                'routine' => $routines[0],
                'routines' => $routines,
                'usesServerPersistence' => true,
            ])
            ->call('createRoutine');

        $this->assertSame(
            3,
            $user->practiceRoutines()->count()
        );

        $component->assertHasErrors([
            'routineLimit',
        ]);

        $component->assertSee(
            'Trial Mode supports up to 3 routines.'
        );
    }

    private function createActiveTrialUser(): User
    {
        $user = User::factory()->create([
            'plan' => 'free',
        ]);

        $trial = new TrialEntitlement();

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

    public function test_trial_can_create_its_third_routine(): void
    {
        $user = $this->createActiveTrialUser();

        foreach (range(0, 1) as $position) {
            $user->practiceRoutines()->create([
                'name' => 'Routine ' . ($position + 1),
                'position' => $position,
                'is_default' => $position === 0,
            ]);
        }

        $component = Livewire::actingAs($user)
            ->test(PracticeDialog::class)
            ->call('createRoutine');

        $this->assertSame(
            3,
            $user->practiceRoutines()->count()
        );

        $createdRoutine = $user->practiceRoutines()
            ->orderByDesc('position')
            ->firstOrFail();

        $this->assertSame(
            'Routine 3',
            $createdRoutine->name
        );

        $this->assertSame(
            1,
            $createdRoutine->steps()->count()
        );

        $component->assertHasNoErrors([
            'routineLimit',
        ]);
    }

    public function test_pro_can_create_more_than_three_routines(): void
    {
        $user = User::factory()->create([
            'plan' => 'pro',
        ]);

        foreach (range(0, 2) as $position) {
            $user->practiceRoutines()->create([
                'name' => 'Routine ' . ($position + 1),
                'position' => $position,
                'is_default' => $position === 0,
            ]);
        }

        $component = Livewire::actingAs($user)
            ->test(PracticeDialog::class)
            ->call('createRoutine');

        $this->assertSame(
            4,
            $user->practiceRoutines()->count()
        );

        $createdRoutine = $user->practiceRoutines()
            ->orderByDesc('position')
            ->firstOrFail();

        $this->assertSame(
            'Routine 4',
            $createdRoutine->name
        );

        $this->assertSame(
            1,
            $createdRoutine->steps()->count()
        );

        $component->assertHasNoErrors([
            'routineLimit',
        ]);
    }

    public function test_trial_cannot_create_a_second_playlist(): void
    {
        $user = $this->createActiveTrialUser();

        $routine = $user->practiceRoutines()->create([
            'name' => 'Routine 1',
            'position' => 0,
            'is_default' => true,
        ]);

        $user->practicePlaylists()->create([
            'name' => 'Playlist 1',
            'starter_routine_id' => null,
            'position' => 0,
        ]);

        $routinePayload = [
            'id' => $routine->id,
            'name' => $routine->name,
            'position' => $routine->position,
            'is_default' => (bool) $routine->is_default,
            'steps_count' => 0,
        ];

        $component = Livewire::actingAs($user)
            ->test(PracticeDialog::class, [
                'routine' => $routinePayload,
                'routines' => [$routinePayload],
                'usesServerPersistence' => true,
            ])
            ->call('showPlaylists')
            ->call('createPlaylist');

        $this->assertSame(
            1,
            $user->practicePlaylists()->count()
        );

        $component->assertHasErrors([
            'playlistLimit',
        ]);

        $component->assertSee(
            'Trial Mode supports up to 1 playlist.'
        );
    }

    public function test_trial_can_create_its_first_playlist(): void
    {
        $user = $this->createActiveTrialUser();

        $component = Livewire::actingAs($user)
            ->test(PracticeDialog::class)
            ->call('createPlaylist');

        $this->assertSame(
            1,
            $user->practicePlaylists()->count()
        );

        $createdPlaylist = $user->practicePlaylists()
            ->firstOrFail();

        $this->assertSame(
            'Playlist 1',
            $createdPlaylist->name
        );

        $this->assertSame(
            0,
            $createdPlaylist->position
        );

        $component->assertHasNoErrors([
            'playlistLimit',
        ]);
    }

    public function test_pro_can_create_more_than_one_playlist(): void
    {
        $user = User::factory()->create([
            'plan' => 'pro',
        ]);

        $user->practicePlaylists()->create([
            'name' => 'Playlist 1',
            'starter_routine_id' => null,
            'position' => 0,
        ]);

        $component = Livewire::actingAs($user)
            ->test(PracticeDialog::class)
            ->call('createPlaylist');

        $this->assertSame(
            2,
            $user->practicePlaylists()->count()
        );

        $createdPlaylist = $user->practicePlaylists()
            ->orderByDesc('position')
            ->firstOrFail();

        $this->assertSame(
            'Playlist 2',
            $createdPlaylist->name
        );

        $this->assertSame(
            1,
            $createdPlaylist->position
        );

        $component->assertHasNoErrors([
            'playlistLimit',
        ]);
    }

    public function test_trial_cannot_bypass_routine_limit_through_http_endpoint(): void
    {
        $user = $this->createActiveTrialUser();

        foreach (range(0, 2) as $position) {
            $user->practiceRoutines()->create([
                'name' => 'Routine ' . ($position + 1),
                'position' => $position,
                'is_default' => $position === 0,
            ]);
        }

        $response = $this
            ->actingAs($user)
            ->post(route('practice-routines.store'));

        $this->assertSame(
            3,
            $user->practiceRoutines()->count()
        );

        $response->assertStatus(422);
    }

    public function test_http_endpoint_creates_only_one_routine(): void
    {
        $user = $this->createActiveTrialUser();

        foreach (range(0, 1) as $position) {
            $user->practiceRoutines()->create([
                'name' => 'Routine ' . ($position + 1),
                'position' => $position,
                'is_default' => $position === 0,
            ]);
        }

        $response = $this
            ->actingAs($user)
            ->post(route('practice-routines.store'));

        $response->assertRedirect();

        $this->assertSame(
            3,
            $user->practiceRoutines()->count()
        );
    }

    public function test_playlist_creator_enforces_trial_limit(): void
    {
        $user = $this->createActiveTrialUser();

        $user->practicePlaylists()->create([
            'name' => 'Playlist 1',
            'starter_routine_id' => null,
            'position' => 0,
        ]);

        $playlist = app(CreatePracticePlaylist::class)
            ->create($user);

        $this->assertNull($playlist);

        $this->assertSame(
            1,
            $user->practicePlaylists()->count()
        );
    }
}