<?php

namespace App\Http\Controllers;

use App\Models\PracticeRoutine;
use App\Models\PracticeRoutineStep;
use App\Services\ExerciseActionsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class PracticeRoutineStepController extends Controller
{
    public function store(
        Request $request,
        PracticeRoutine $practiceRoutine
    ): JsonResponse {
        abort_unless(
            (int) $practiceRoutine->user_id
                === (int) $request->user()->id,
            403
        );

        $exerciseLimit = $request->user()->exerciseLimit();

        // temporal: por ahora conserva limite actual Free
        abort_if(
            $practiceRoutine->steps()->count() >= $exerciseLimit,
            422,
            'Exercise limit reached'
        );

        $durationLimit = $request->user()->exerciseDurationLimit();

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'bpm' => [
                'required',
                'integer',
                'min:30',
                'max:400',
            ],

            'mode' => [
                'required',
                'in:drill,classic',
            ],

            'duration_seconds' => [
                'required_if:mode,drill',
                'nullable',
                'integer',
                'min:1',
                "max:{$durationLimit}",
            ],

            'time_signature_numerator' => [
                'sometimes',
                'integer',
                'min:2',
                'max:16',
            ],

            'time_signature_denominator' => [
                'sometimes',
                'integer',
                'in:4',
            ],
        ]);

        $validated['time_signature_numerator'] ??= 4;
        $validated['time_signature_denominator'] ??= 4;

        if ($validated['mode'] === 'classic') {
            $validated['duration_seconds'] = null;
        }

        $nextPosition =
            ((int) $practiceRoutine->steps()->max('position')) + 1;

        $step = $practiceRoutine->steps()->create([
            ...$validated,
            'position' => $nextPosition,
            'origin' => 'custom',
        ]);

        return response()->json($step, 201);
    }

    public function update(
        Request $request,
        PracticeRoutineStep $practiceRoutineStep
    ): JsonResponse {
        abort_unless(
            (int) $practiceRoutineStep->routine?->user_id
                === (int) $request->user()->id,
            403
        );

        $durationLimit = $request->user()->exerciseDurationLimit();

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'bpm' => [
                'required',
                'integer',
                'min:30',
                'max:400',
            ],

            'mode' => [
                'required',
                'in:drill,classic',
            ],

            'duration_seconds' => [
                'required_if:mode,drill',
                'nullable',
                'integer',
                'min:1',
                "max:{$durationLimit}",
            ],

            'time_signature_numerator' => [
                'sometimes',
                'integer',
                'min:2',
                'max:16',
            ],

            'time_signature_denominator' => [
                'sometimes',
                'integer',
                'in:4',
            ],
        ]);

        if ($validated['mode'] === 'classic') {
            $validated['duration_seconds'] = null;
        }

        $practiceRoutineStep->fill([
            ...$validated,
            'origin' => 'custom',
        ]);

        $practiceRoutineStep->save();
        $practiceRoutineStep->refresh();

        return response()->json($practiceRoutineStep);
    }

    public function move(Request $request, PracticeRoutineStep $practiceRoutineStep, ExerciseActionsService $actions): JsonResponse
    {
        abort_unless((int) $practiceRoutineStep->routine?->user_id === (int) $request->user()->id, 403);
        $data = $request->validate(['direction' => ['required', 'in:up,down']]);
        $actions->move($practiceRoutineStep->routine, $practiceRoutineStep->id, $data['direction']);
        return response()->json(['ok' => true]);
    }

    public function duplicate(Request $request, PracticeRoutineStep $practiceRoutineStep, ExerciseActionsService $actions): JsonResponse
    {
        abort_unless((int) $practiceRoutineStep->routine?->user_id === (int) $request->user()->id, 403);
        $copy = $actions->duplicate($practiceRoutineStep->routine, $practiceRoutineStep->id, $request->user()->exerciseLimit());
        return response()->json($copy, 201);
    }

    public function destroy(
        Request $request,
        PracticeRoutineStep $practiceRoutineStep
    ) {
        abort_unless(
            (int) $practiceRoutineStep->routine?->user_id
                === (int) $request->user()->id,
            403
        );

        $snapshot = app(ExerciseActionsService::class)->delete($practiceRoutineStep->routine, $practiceRoutineStep->id);
        $token = Str::random(48);
        Cache::put('exercise-undo:' . $token, [
            'user_id' => $request->user()->id,
            'routine_id' => $practiceRoutineStep->practice_routine_id,
            'snapshot' => $snapshot,
        ], now()->addSeconds(20));

        return response()->json(['undo_token' => $token]);
    }
    public function restoreDeleted(Request $request, ExerciseActionsService $actions): JsonResponse
    {
        $data = $request->validate(['undo_token' => ['required', 'string', 'size:48']]);
        $key = 'exercise-undo:' . $data['undo_token'];
        $saved = Cache::get($key);
        abort_if(! $saved, 410, 'Undo expired');
        abort_unless((int) $saved['user_id'] === (int) $request->user()->id, 403);

        $routine = $request->user()->practiceRoutines()->findOrFail($saved['routine_id']);
        $restored = $actions->restore($routine, $saved['snapshot'], $request->user()->exerciseLimit());
        Cache::forget($key);
        return response()->json($restored, 201);
    }

}
