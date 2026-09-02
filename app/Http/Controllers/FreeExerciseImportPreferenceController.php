<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FreeExerciseImportPreferenceController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'enabled' => [
                'required',
                'boolean',
            ],
        ]);

        $request->user()->forceFill([
            'ask_before_importing_free_exercises' =>
                $validated['enabled'],
        ])->save();

        return response()->json([
            'ask_before_importing_free_exercises' =>
                (bool) $validated['enabled'],
        ]);
    }
}
