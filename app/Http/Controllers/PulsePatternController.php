<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePulsePatternRequest;
use App\Http\Requests\UpdatePulsePatternRequest;
use App\Models\PulsePattern;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PulsePatternController extends Controller
{
    public function store(
        StorePulsePatternRequest $request
    ): JsonResponse {
        $pulsePattern = $request
            ->user()
            ->pulsePatterns()
            ->create(
                $request->validated()
            );

        return response()->json([
            'id' => $pulsePattern->id,
            'name' => $pulsePattern->name,

            'timeSignature' => [
                'numerator' => $pulsePattern->numerator,
                'denominator' => $pulsePattern->denominator
            ],

            'grouping' => $pulsePattern->grouping,
            'pattern' => $pulsePattern->pattern
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $pulsePatterns = $request
            ->user()
            ->pulsePatterns()
            ->latest()
            ->get()
            ->map(fn ($pulsePattern) => [
                'id' => $pulsePattern->id,
                'name' => $pulsePattern->name,

                'timeSignature' => [
                    'numerator' => $pulsePattern->numerator,
                    'denominator' => $pulsePattern->denominator,
                ],

                'grouping' => $pulsePattern->grouping,
                'pattern' => $pulsePattern->pattern,
            ]);

        return response()->json($pulsePatterns);
    }

    public function update(
        UpdatePulsePatternRequest $request,
        PulsePattern $pulsePattern
    ): JsonResponse {
        abort_unless(
            $pulsePattern->user_id === $request->user()->id,
            403
        );

        $pulsePattern->fill($request->validated());
        $pulsePattern->save();

        return response()->json([
            'id' => $pulsePattern->id,
            'name' => $pulsePattern->name,

            'timeSignature' => [
                'numerator' => $pulsePattern->numerator,
                'denominator' => $pulsePattern->denominator,
            ],

            'grouping' => $pulsePattern->grouping,
            'pattern' => $pulsePattern->pattern,
        ]);
    }

    public function destroy(
        Request $request,
        PulsePattern $pulsePattern
    ): JsonResponse {
        abort_unless(
            $pulsePattern->user_id === $request->user()->id,
            403
        );

        PulsePattern::destroy($pulsePattern->id);

        return response()->json([
            'deleted' => true,
            'id' => $pulsePattern->id,
        ]);
    }
}
