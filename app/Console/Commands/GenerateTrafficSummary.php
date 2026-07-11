<?php

namespace App\Console\Commands;

use App\Services\Traffic\NginxLogParser;
use App\Services\Traffic\TrafficClassifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class GenerateTrafficSummary extends Command
{
    protected $signature = 'traffic:generate-summary
        {--log= : Path to the Nginx access log}
        {--session-gap=30 : Minutes of inactivity before starting a new session}';

    protected $description = 'Generate traffic summary from Nginx access log';

    public function handle(NginxLogParser $parser, TrafficClassifier $classifier): int
    {
        $logPath = $this->option('log')
            ?: config('traffic.access_log_path');

        $sessionGapMinutes = (int) $this->option('session-gap');

        if (! is_readable($logPath)) {
            $this->error("Log file is not readable: {$logPath}");

            return self::FAILURE;
        }

        $lines = file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        $requests = [];
        $skipped = 0;

        foreach ($lines as $line) {
            $parsed = $parser->parse($line);

            if ($parsed === null) {
                $skipped++;

                continue;
            }

            $requests[] = $parsed;
        }

        usort($requests, function ($a, $b) {
            return ($a['timestamp'] ?? 0) <=> ($b['timestamp'] ?? 0);
        });

        $sessions = $this->buildSessions(
            requests: $requests,
            classifier: $classifier,
            sessionGapMinutes: $sessionGapMinutes
        );

        $summaryCounts = collect($sessions)
            ->groupBy('classification')
            ->map(fn ($items) => $items->count());

        $this->line(
            json_encode(
                $summaryCounts->all(),
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
            )
        );

        $summary = [
            'generated_at' => now()->toIso8601String(),
            'log_path' => $logPath,
            'session_gap_minutes' => $sessionGapMinutes,
            'total_lines' => count($lines),
            'total_requests' => count($requests),
            'skipped_lines' => $skipped,
            'total_sessions' => count($sessions),
            'summary' => [
                'human_like' => $summaryCounts->get('human_like', 0),
                'scanner' => $summaryCounts->get('scanner', 0),
                'internal' => $summaryCounts->get('internal', 0),
                'unknown' => $summaryCounts->get('unknown', 0),
            ],
            'sessions' => $sessions,
        ];

        Storage::disk('local')->put(
            'traffic/traffic-summary.json',
            json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        $this->info('Traffic summary generated');
        $this->line('Requests: ' . count($requests));
        $this->line('Sessions: ' . count($sessions));
        $this->line('Skipped lines: ' . $skipped);

        return self::SUCCESS;
    }

    private function buildSessions(array $requests, TrafficClassifier $classifier, int $sessionGapMinutes): array
    {
        $gapSeconds = $sessionGapMinutes * 60;

        $groups = collect($requests)->groupBy(function ($request) {
            $ip = $request['ip'] ?? 'unknown-ip';
            $userAgent = $request['user_agent'] ?? 'unknown-user-agent';

            return $ip . '|' . sha1($userAgent);
        });

        $sessions = [];

        foreach ($groups as $groupKey => $groupRequests) {
            $groupRequests = $groupRequests
                ->sortBy(fn ($request) => $request['timestamp'] ?? 0)
                ->values();

            $currentSessionRequests = [];
            $lastTimestamp = null;

            foreach ($groupRequests as $request) {
                $currentTimestamp = $request['timestamp'] ?? null;

                $shouldStartNewSession = $lastTimestamp !== null
                    && $currentTimestamp !== null
                    && ($currentTimestamp - $lastTimestamp) > $gapSeconds;

                if ($shouldStartNewSession && count($currentSessionRequests) > 0) {
                    $sessions[] = $this->makeSession(
                        requests: $currentSessionRequests,
                        classifier: $classifier,
                        groupKey: $groupKey
                    );

                    $currentSessionRequests = [];
                }

                $currentSessionRequests[] = $request;
                $lastTimestamp = $currentTimestamp;
            }

            if (count($currentSessionRequests) > 0) {
                $sessions[] = $this->makeSession(
                    requests: $currentSessionRequests,
                    classifier: $classifier,
                    groupKey: $groupKey
                );
            }
        }

        usort($sessions, function ($a, $b) {
            return ($b['last_seen_timestamp'] ?? 0) <=> ($a['last_seen_timestamp'] ?? 0);
        });

        return $sessions;
    }

    private function makeSession(array $requests, TrafficClassifier $classifier, string $groupKey): array
    {
        $classification = $classifier->classify($requests);

        $timestamps = collect($requests)
            ->pluck('timestamp')
            ->filter()
            ->values();

        $firstSeenTimestamp = $timestamps->min();
        $lastSeenTimestamp = $timestamps->max();

        $paths = collect($requests)
            ->pluck('path')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $statusCounts = collect($requests)
            ->groupBy(fn ($request) => (string) ($request['status'] ?? 'unknown'))
            ->map(fn ($items) => $items->count())
            ->all();

        $methodCounts = collect($requests)
            ->groupBy(fn ($request) => (string) ($request['method'] ?? 'unknown'))
            ->map(fn ($items) => $items->count())
            ->all();

        return array_merge([
            'session_id' => substr(sha1($groupKey . '|' . $firstSeenTimestamp . '|' . $lastSeenTimestamp), 0, 16),
            'ip' => $requests[0]['ip'] ?? null,
            'user_agent' => $requests[0]['user_agent'] ?? null,
            'first_seen' => $firstSeenTimestamp ? date('c', $firstSeenTimestamp) : null,
            'last_seen' => $lastSeenTimestamp ? date('c', $lastSeenTimestamp) : null,
            'first_seen_timestamp' => $firstSeenTimestamp,
            'last_seen_timestamp' => $lastSeenTimestamp,
            'duration_seconds' => ($firstSeenTimestamp && $lastSeenTimestamp)
                ? max(0, $lastSeenTimestamp - $firstSeenTimestamp)
                : 0,
            'requests_count' => count($requests),
            'paths' => $paths,
            'status_counts' => $statusCounts,
            'method_counts' => $methodCounts,
        ], $classification, [
            'requests' => $requests,
        ]);
    }
}