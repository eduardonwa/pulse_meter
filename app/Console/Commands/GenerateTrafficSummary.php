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
        {--session-gap=30 : Minutes of inactivity before starting a new session}
        {--days=7 : Number of recent days to include}';

    protected $description = 'Generate traffic summary from Nginx access log';

    public function handle(NginxLogParser $parser, TrafficClassifier $classifier): int
    {
        $logPath = $this->option('log')
            ?: config('traffic.access_log_path');

        $sessionGapMinutes = (int) $this->option('session-gap');
        
        $days = max(1, (int) $this->option('days'));

        $timezone = config(
            'traffic.history_timezone',
            'America/Hermosillo'
        );

        // Incluye hoy y los seis días anteriores en la zona del usuario.
        $cutoffTimestamp = now($timezone)
            ->subDays($days - 1)
            ->startOfDay()
            ->utc()
            ->timestamp;

        $logFiles = $this->findLogFiles($logPath);

        if ($logFiles === []) {
            $this->error("No readable log files found for: {$logPath}");

            return self::FAILURE;
        }

        $requests = [];
        $skipped = 0;
        $totalLines = 0;
        $outsideWindow = 0;

        foreach ($logFiles as $file) {
            foreach ($this->readLogLines($file) as $line) {
                $totalLines++;

                $parsed = $parser->parse($line);

                if ($parsed === null) {
                    $skipped++;

                    continue;
                }

                $timestamp = $parsed['timestamp'] ?? null;

                if (! is_numeric($timestamp)) {
                    $skipped++;

                    continue;
                }

                if ((int) $timestamp < $cutoffTimestamp) {
                    $outsideWindow++;

                    continue;
                }

                $requests[] = $parsed;
            }
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
            'log_files' => $logFiles,
            'history_days' => $days,
            'history_timezone' => $timezone,
            'cutoff_timestamp' => $cutoffTimestamp,
            'session_gap_minutes' => $sessionGapMinutes,
            'total_lines' => $totalLines,
            'total_requests' => count($requests),
            'skipped_lines' => $skipped,
            'outside_history_window' => $outsideWindow,
            'total_sessions' => count($sessions),
            'summary' => [
                'browser_like' => $summaryCounts->get('browser_like', 0),
                'scanner' => $summaryCounts->get('scanner', 0),
                'automation_suspected' => $summaryCounts->get('automation_suspected', 0),
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
        $this->line('Log files read: ' . count($logFiles));
        $this->line('Requests in last ' . $days . ' days: ' . count($requests));
        $this->line('Older requests ignored: ' . $outsideWindow);

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

    private function findLogFiles(string $logPath): array
    {
        $files = glob($logPath . '*') ?: [];

        $pattern = '#^'
            . preg_quote($logPath, '#')
            . '(?:\.\d+)?(?:\.gz)?$#';

        return array_values(
            array_filter(
                $files,
                fn (string $file): bool =>
                    is_file($file)
                    && is_readable($file)
                    && preg_match($pattern, $file) === 1
            )
        );
    }

    private function readLogLines(string $path): \Generator
    {
        if (str_ends_with($path, '.gz')) {
            $handle = gzopen($path, 'rb');

            if ($handle === false) {
                return;
            }

            try {
                while (($line = gzgets($handle)) !== false) {
                    $line = rtrim($line, "\r\n");

                    if ($line !== '') {
                        yield $line;
                    }
                }
            } finally {
                gzclose($handle);
            }

            return;
        }

        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return;
        }

        try {
            while (($line = fgets($handle)) !== false) {
                $line = rtrim($line, "\r\n");

                if ($line !== '') {
                    yield $line;
                }
            }
        } finally {
            fclose($handle);
        }
    }
}