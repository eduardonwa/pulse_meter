<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ProfileHomeRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('dorelog-profile.home_enabled', false)) {
            return $next($request);
        }

        // LARAVEL_START is defined at the beginning of public/index.php.
        // This measures time before this route middleware starts.
        $entryToMiddlewareMs = defined('LARAVEL_START')
            ? (microtime(true) - LARAVEL_START) * 1000
            : null;
        $startedAt = hrtime(true);
        $queryCount = 0;
        $queryTimeMs = 0.0;

        DB::listen(function (QueryExecuted $query) use (&$queryCount, &$queryTimeMs): void {
            $queryCount++;
            $queryTimeMs += $query->time;
        });

        $response = null;

        try {
            $response = $next($request);

            return $response;
        } finally {
            $elapsedMs = (hrtime(true) - $startedAt) / 1_000_000;
            $entryToMiddlewareRoundedMs = $entryToMiddlewareMs === null
                ? null
                : round($entryToMiddlewareMs, 2);

            Log::info('dorelog.home_profile', [
                'status' => $response?->getStatusCode(),
                'duration_ms' => round($elapsedMs, 2),
                'entry_to_middleware_ms' => $entryToMiddlewareRoundedMs,
                'entry_to_response_ms' => $entryToMiddlewareMs === null
                    ? null
                    : round($entryToMiddlewareMs + $elapsedMs, 2),
                'db_query_count' => $queryCount,
                'db_query_ms' => round($queryTimeMs, 2),
                'other_ms' => round(max(0, $elapsedMs - $queryTimeMs), 2),
            ]);
        }
    }
}
