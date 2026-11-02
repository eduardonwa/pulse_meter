<?php

namespace Tests\Feature;

use App\Models\PracticeRoutine;
use App\Models\SharedRoutine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SharedRoutinesTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_shared_link_survives_a_plan_downgrade(): void
    {
        $owner = User::factory()->create(['plan' => 'pro']);
        $routine = $owner->practiceRoutines()->create(['name' => 'Picking', 'position' => 0, 'is_default' => true]);

        $this->actingAs($owner)->post(route('shared.store', $routine))->assertRedirect();
        $publication = SharedRoutine::firstOrFail();

        $owner->update(['plan' => 'free']);
        $this->get(route('shared.show', ['locale' => 'en', 'token' => $publication->token]))->assertOk();

        $this->actingAs($owner)->patch(route('shared.toggle', $publication))->assertRedirect();
        $this->get(route('shared.show', ['locale' => 'en', 'token' => $publication->token]))->assertNotFound();
        $this->actingAs($owner)->patch(route('shared.toggle', $publication))->assertRedirect();
        $this->get(route('shared.show', ['locale' => 'en', 'token' => $publication->token]))->assertOk();
    }

    public function test_descriptive_and_legacy_links_resolve_to_same_routine(): void
    {
        $owner = User::factory()->create(['plan' => 'pro']);
        $routine = $owner->practiceRoutines()->create([
            'name' => 'Alternate Picking Workout', 'position' => 0, 'is_default' => true,
        ]);

        $this->actingAs($owner)->post(route('shared.store', $routine))->assertRedirect();
        $share = SharedRoutine::firstOrFail();

        $this->assertStringStartsWith('alternate-picking-workout-', $share->slug);
        $this->get($share->publicUrl('en'))->assertOk();
        $this->get(route('shared.show', ['locale' => 'en', 'token' => $share->token]))->assertOk();

        $routine->update(['name' => 'Renamed Workout']);
        $this->assertSame($share->slug, $share->fresh()->slug);
        $this->get($share->publicUrl('en'))->assertOk();
    }

    public function test_deleting_original_does_not_delete_imported_copy(): void
    {
        $owner = User::factory()->create(['plan' => 'pro']);
        $consumer = User::factory()->create(['plan' => 'pro']);
        $source = $owner->practiceRoutines()->create(['name' => 'Source', 'position' => 0, 'is_default' => true]);
        $publication = new SharedRoutine();
        $publication->user_id = $owner->id;
        $publication->practice_routine_id = $source->id;
        $publication->token = str_repeat('a', 48);
        $publication->slug = 'source-routine-aaaaaaaaaa';
        $publication->is_active = true;
        $publication->save();

        $copy = $consumer->practiceRoutines()->create([
            'name' => 'Personal copy', 'position' => 0,
            'is_default' => true, 'shared_routine_id' => $publication->id,
        ]);
        $source->delete();
        $this->assertDatabaseHas('practice_routines', ['id' => $copy->id, 'shared_routine_id' => null]);
        $this->assertDatabaseMissing('shared_routines', ['id' => $publication->id]);
    }
}
