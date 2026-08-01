<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePulsePresetRequest;
use App\Http\Requests\UpdatePulsePresetRequest;
use App\Models\PulsePreset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PulsePresetController extends Controller
{
    public function store(
        StorePulsePresetRequest $request
    ): JsonResponse {
        $position = $request
            ->user()
            ->pulsePresets()
            ->max('position') ?? 0;

        $pulsePreset = $request
            ->user()
            ->pulsePresets()
            ->create([
                ...$request->validated(),
                'position' => $position + 1,
            ]);

        return response()->json([
            'id' => $pulsePreset->id,
            'name' => $pulsePreset->name,
            'position' => $pulsePreset->position,

            'timeSignature' => [
                'numerator' => $pulsePreset->numerator,
                'denominator' => $pulsePreset->denominator,
            ],

            'grouping' => $pulsePreset->grouping,
            'pattern' => $pulsePreset->pattern,
        ], 201);
    }

    public function index(
        Request $request
    ): JsonResponse
    {
        $pulsePresets = $request
            ->user()
            ->pulsePresets()
            ->orderBy('position')
            ->get()
            ->map(fn ($pulsePreset) => [
                'id' => $pulsePreset->id,
                'name' => $pulsePreset->name,
                'position' => $pulsePreset->position,

                'timeSignature' => [
                    'numerator' => $pulsePreset->numerator,
                    'denominator' => $pulsePreset->denominator,
                ],

                'grouping' => $pulsePreset->grouping,
                'pattern' => $pulsePreset->pattern,
            ]);

        return response()->json($pulsePresets);
    }

    public function update(
        UpdatePulsePresetRequest $request,
        PulsePreset $pulsePreset
    ): JsonResponse {
        abort_unless(
            $pulsePreset->user_id === $request->user()->id,
            403
        );

        $pulsePreset->fill($request->validated());
        $pulsePreset->save();

        return response()->json([
            'id' => $pulsePreset->id,
            'name' => $pulsePreset->name,
            'position' => $pulsePreset->position,

            'timeSignature' => [
                'numerator' => $pulsePreset->numerator,
                'denominator' => $pulsePreset->denominator,
            ],

            'grouping' => $pulsePreset->grouping,
            'pattern' => $pulsePreset->pattern,
        ]);
    }

    public function destroy(
        Request $request,
        PulsePreset $pulsePreset
    ): JsonResponse {
        abort_unless(
            $pulsePreset->user_id === $request->user()->id,
            403
        );

        $pulsePreset->delete($pulsePreset->id);

        return response()->json([
            'deleted' => true,
            'id' => $pulsePreset->id,
        ]);
    }
}