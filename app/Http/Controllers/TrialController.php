<?php

namespace App\Http\Controllers;

use App\Services\TrialMode\Heartbeat as TrialHeartbeat;
use App\Services\TrialMode\Resume as ResumeTrialMode;
use App\Services\TrialMode\Pause as PauseTrialMode;
use App\Services\TrialMode\Activate as ActivateTrialMode;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TrialController extends Controller
{
    public function heartbeat(Request $request, TrialHeartbeat $heartbeat): JsonResponse
    {
        return $heartbeat->heartbeat($request);
    }

    public function activate(Request $request, ActivateTrialMode $activate): RedirectResponse
    {
        return $activate->activate($request);
    }

    public function resume(Request $request, ResumeTrialMode $resume): RedirectResponse {
        return $resume->resume($request);
    }

    public function pause(Request $request, PauseTrialMode $pause): RedirectResponse {
        return $pause->pause($request);
    }
}