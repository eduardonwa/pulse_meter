<?php

namespace App\Http\Controllers;

use App\Models\RoutineTemplate;
use App\Services\Practice\CreateRoutineFromTemplate;
use Illuminate\Http\Request;

class SaveRoutineTemplateController extends Controller
{
    public function __invoke(
        Request $request,
        RoutineTemplate $routineTemplate,
        CreateRoutineFromTemplate $creator,
    ) {
        $user = $request->user();

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:80',
            ],

            'steps' => [
                'required',
                'array',
                'min:1',
                'max:20',
            ],

            'steps.*.name' => [
                'required',
                'string',
                'max:255',
            ],

            'steps.*.bpm' => [
                'required',
                'integer',
                'min:30',
                'max:400',
            ],

            'steps.*.mode' => [
                'required',
                'in:timer,classic',
            ],

            'steps.*.duration_seconds' => [
                'nullable',
                'integer',
                'min:1',
                'max:900',
            ],
        ]);

        $routine = $creator->create(
            $user,
            $routineTemplate->id,
            $validated['name'],
            $validated['steps'],
        );

        return response()->json([
            'status' => 'saved',
            'routine_id' => $routine->id,

            'redirect_url' => route('welcome', [
                'routine' => $routine->id,
            ]),
        ]);
    }
}