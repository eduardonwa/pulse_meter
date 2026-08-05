<?php

namespace App\Http\Controllers;

use App\Models\PracticeRoutine;
use App\Models\PracticeRoutineStep;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PracticeRoutineStepController extends Controller
{
    public function store(
        Request $request,
        PracticeRoutine $practiceRoutine
    ): JsonResponse
    {
        abort_unless(
            $practiceRoutine->user_id === $request->user()->id,
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
                'in:timer,classic',
            ],

            'duration_seconds' => [
                'required_if:mode,timer',
                'nullable',
                'integer',
                'min:1',
                "max:{$durationLimit}"
            ],
        ]);

        if ($validated['mode'] === 'classic') {
            $validated['duration_seconds'] = null;
        }

        $nextPosition =
            ((int) $practiceRoutine->steps()->max('position')) + 1;
        
        $step = $practiceRoutine->steps()->create([
            ...$validated,
            'position' => $nextPosition,
            'origin' => 'custom'
        ]);

        return response()->json($step, 201);
    }

    public function update(
        Request $request,
        PracticeRoutineStep $practiceRoutineStep
    ): JsonResponse {
        abort_unless(
            $practiceRoutineStep->routine?->user_id
                === $request->user()->id,
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
                'max:300',
            ],

            'mode' => [
                'required',
                'in:timer,classic',
            ],

            'duration_seconds' => [
                'required_if:mode,timer',
                'nullable',
                'integer',
                'min:1',
                "max:{$durationLimit}",
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

    public function destroy(
        Request $request,
        PracticeRoutineStep $practiceRoutineStep
    ) {
        abort_unless(
            $practiceRoutineStep->routine?->user_id
                === $request->user()->id,
            403
        );

        if (
            $practiceRoutineStep
                ->routine
                ->steps()
                ->count() <= 1
        ) {
            return response()->json([
                'message' => 'A routine must have at least one exercise.',
            ], 422);
        }

        $practiceRoutineStep->delete($practiceRoutineStep->id);

        return response()->noContent();
    }
}