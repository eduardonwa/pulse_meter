<?php
namespace App\Http\Controllers;

use App\Models\PracticeRoutine;
use App\Models\SharedRoutine;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SharedRoutineController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        return view('shared.index', [
            'canPublish' => $user->hasPaidProAccess(),
            'myRoutines' => $user->practiceRoutines()->with('sharedPublication')->orderBy('position')->get(),
            'published' => SharedRoutine::where('user_id', $user->id)->with('routine.steps')->get(),
            'imported' => $user->practiceRoutines()->whereNotNull('shared_routine_id')->with('importedFrom')->get(),
        ]);
    }

    public function store(Request $request, PracticeRoutine $practiceRoutine)
    {
        abort_unless($request->user()->hasPaidProAccess(), 403);
        abort_unless($practiceRoutine->user_id === $request->user()->id, 404);
        SharedRoutine::firstOrCreate(
            ['practice_routine_id' => $practiceRoutine->id],
            ['user_id' => $request->user()->id, 'token' => Str::random(48), 'slug' => Str::slug($practiceRoutine->name) . '-' . Str::lower(Str::random(10)), 'is_active' => true]
        );
        return redirect()->route('account.my-routines');
    }

    public function toggle(Request $request, SharedRoutine $sharedRoutine)
    {
        abort_unless($sharedRoutine->user_id === $request->user()->id, 404);
        $sharedRoutine->is_active = ! $sharedRoutine->is_active;
        $sharedRoutine->save();
        return redirect()->route('account.my-routines');
    }

    public function show(Request $request, string $locale, string $token)
    {
        $publication = SharedRoutine::where(fn ($query) => $query->where('slug', $token)->orWhere('token', $token))->where('is_active', true)
            ->with(['routine.steps', 'user'])->firstOrFail();
        $original = $publication->routine;
        abort_unless($original && $original->user_id === $publication->user_id, 404);
        $user = $request->user();
        $viewerType = match (true) {
            ! $user => 'guest',
            $user->hasLifetimePro() => 'lifetime',
            $user->isPro() => 'pro',
            $user->hasActiveTrial() => 'trial',
            default => 'free',
        };
        $queue = $original->steps->take(10)->map(fn ($s) => [
            'id' => $s->id, 'name' => $s->name, 'bpm' => $s->bpm,
            'mode' => $s->mode, 'duration_seconds' => $s->duration_seconds,
            'time_signature_numerator' => $s->time_signature_numerator,
            'time_signature_denominator' => $s->time_signature_denominator,
            'alpha_tex' => $s->alpha_tex, 'position' => $s->position,
            'origin' => $s->origin,
        ])->all();
        return view('shared.show', [
            'publication' => $publication, 'original' => $original,
            'routine' => (object) ['title' => $original->name],
            'template' => $original, 'isShared' => true,
            'publicSteps' => $original->steps->take(10),
            'viewerType' => $viewerType,
            'viewerHasTemplate' => $user ? $user->practiceRoutines()->where('shared_routine_id', $publication->id)->exists() : false,
            'practiceContext' => ['mode' => 'routine', 'persistence' => 'template',
                'active_routine' => null, 'active_playlist' => null,
                'groups' => [], 'queue' => $queue],
            'pulsePresets' => \App\Models\PulsePreset::whereNull('user_id')->orderBy('id')->get(),
            'coverUrl' => null, 'initialStepName' => $original->steps->first()?->name ?? 'Exercise',
        ]);
    }

    public function saveCopy(Request $request, SharedRoutine $sharedRoutine)
    {
        $user = $request->user();
        abort_unless($user->hasPaidProAccess(), 403);
        abort_unless($sharedRoutine->is_active, 404);
        $original = $sharedRoutine->routine()->with('steps')->firstOrFail();
        abort_unless($original->user_id === $sharedRoutine->user_id, 404);
        abort_if($original->user_id === $user->id, 422);
        abort_if($original->steps->isEmpty(), 422);
        $routine = \Illuminate\Support\Facades\DB::transaction(function () use ($user, $sharedRoutine, $original) {
            $locked = $user->newQuery()->lockForUpdate()->findOrFail($user->id);
            $position = $locked->practiceRoutines()->max('position');
            $copy = $locked->practiceRoutines()->create([
                'name' => $original->name, 'position' => $position === null ? 0 : $position + 1,
                'is_default' => false, 'shared_routine_id' => $sharedRoutine->id,
            ]);
            $copy->steps()->createMany($original->steps->take(10)->values()->map(fn ($s, $i) => [
                'name' => $s->name, 'bpm' => $s->bpm, 'mode' => $s->mode,
                'duration_seconds' => $s->duration_seconds,
                'time_signature_numerator' => $s->time_signature_numerator,
                'time_signature_denominator' => $s->time_signature_denominator,
                'alpha_tex' => $s->alpha_tex,
                'tab_source' => filled($s->alpha_tex) ? 'template' : null,
                'position' => $i, 'origin' => 'custom',
            ])->all());
            return $copy;
        });
        return response()->json([
            'status' => 'saved', 'routine_id' => $routine->id,
            'redirect_url' => route('welcome', ['routine' => $routine->id]),
        ]);
    }
}
