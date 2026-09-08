<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRandomizedSessionRequest;
use App\Http\Requests\UpdateRandomizedSessionRequest;
use App\Models\RandomizedSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RandomizedSessionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $sessions = $request
            ->user()
            ->randomizedSessions()
            ->orderBy('position')
            ->get()
            ->map(fn (RandomizedSession $session) =>
                $this->payload($session)
            );

        return response()->json($sessions);
    }

    public function store(
        StoreRandomizedSessionRequest $request
    ): JsonResponse {
        $position = $request
            ->user()
            ->randomizedSessions()
            ->max('position') ?? 0;

        $session = $request
            ->user()
            ->randomizedSessions()
            ->create([
                ...$request->validated(),
                'position' => $position + 1,
            ]);

        return response()->json(
            $this->payload($session),
            201
        );
    }

    public function update(
        UpdateRandomizedSessionRequest $request,
        RandomizedSession $randomizedSession
    ): JsonResponse {
        $this->authorizeOwner($request, $randomizedSession);

        $randomizedSession->fill($request->validated());
        $randomizedSession->save();

        return response()->json(
            $this->payload($randomizedSession)
        );
    }

    public function destroy(
        Request $request,
        RandomizedSession $randomizedSession
    ): JsonResponse {
        $this->authorizeOwner($request, $randomizedSession);

        $randomizedSession->delete();

        return response()->json([
            'deleted' => true,
            'id' => $randomizedSession->id,
        ]);
    }

    private function authorizeOwner(
        Request $request,
        RandomizedSession $session
    ): void {
        abort_unless(
            $session->user_id === $request->user()->id,
            403
        );
    }

    private function payload(RandomizedSession $session): array
    {
        return [
            'id' => $session->id,
            'name' => $session->name,
            'position' => $session->position,
            'bpm' => $session->bpm,
            'root' => $session->root,
            'scale' => $session->scale,
            'playbackMode' => $session->playback_mode,
            'timeSignature' => [
                'numerator' => $session->numerator,
                'denominator' => $session->denominator,
            ],
            'subdivision' => $session->subdivision,
            'grouping' => $session->grouping,
            'pattern' => $session->pattern,
        ];
    }
}
