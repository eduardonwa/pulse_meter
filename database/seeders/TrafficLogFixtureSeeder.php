<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class TrafficLogFixtureSeeder extends Seeder
{
    private const SUMMARY_PATH = 'traffic/traffic-summary.json';

    private const RAW_LOG_PATH = 'traffic/fixture-access.log';

    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException(
                'TrafficLogFixtureSeeder must not run in production.'
            );
        }

        /*
         * Deben coincidir con ProductEventFixtureSeeder para que
         * TrafficSessionCorrelator una los eventos con esta visita.
         */
        $ipAddress = '203.0.113.10';

        $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) '
            . 'AppleWebKit/537.36 Chrome/126.0 Safari/537.36';

        /*
         * La visita siempre se fabrica para el momento actual.
         * Los ProductEvent pueden quedar, por ejemplo, en:
         *
         * now()->utc()->subMinutes(2)
         * now()->utc()->subMinute()
         */
        $baseTime = now('UTC');

        $requests = [
            $this->request(
                occurredAt: $baseTime->copy()->subMinutes(3),
                method: 'GET',
                path: '/',
                status: 200,
                bytes: 5758,
                ipAddress: $ipAddress,
                userAgent: $userAgent,
                referrer: '-',
            ),
            $this->request(
                occurredAt: $baseTime->copy()->subMinutes(3)->addSeconds(2),
                method: 'GET',
                path: '/build/assets/app-fixture.js',
                status: 200,
                bytes: 48231,
                ipAddress: $ipAddress,
                userAgent: $userAgent,
                referrer: 'https://dorelog.test/',
            ),
            $this->request(
                occurredAt: $baseTime->copy()->subMinutes(2)->addSeconds(5),
                method: 'POST',
                path: '/analytics/events',
                status: 204,
                bytes: 0,
                ipAddress: $ipAddress,
                userAgent: $userAgent,
                referrer: 'https://dorelog.test/',
            ),
            $this->request(
                occurredAt: $baseTime->copy()->subMinute()->subSeconds(10),
                method: 'GET',
                path: '/pricing',
                status: 200,
                bytes: 4200,
                ipAddress: $ipAddress,
                userAgent: $userAgent,
                referrer: 'https://dorelog.test/',
            ),
            $this->request(
                occurredAt: $baseTime->copy()->subMinute()->addSeconds(5),
                method: 'POST',
                path: '/analytics/events',
                status: 204,
                bytes: 0,
                ipAddress: $ipAddress,
                userAgent: $userAgent,
                referrer: 'https://dorelog.test/pricing',
            ),
            $this->request(
                occurredAt: $baseTime->copy()->subSeconds(5),
                method: 'GET',
                path: '/app',
                status: 200,
                bytes: 6100,
                ipAddress: $ipAddress,
                userAgent: $userAgent,
                referrer: 'https://dorelog.test/pricing',
            ),
        ];

        $firstSeen = Carbon::parse($requests[0]['occurred_at'], 'UTC');
        $lastSeen = Carbon::parse(
            $requests[array_key_last($requests)]['occurred_at'],
            'UTC'
        );

        $session = [
            'ip' => $ipAddress,
            'user_agent' => $userAgent,

            'first_seen' => $firstSeen->toIso8601String(),
            'last_seen' => $lastSeen->toIso8601String(),

            'first_seen_timestamp' => $firstSeen->timestamp,
            'last_seen_timestamp' => $lastSeen->timestamp,

            'duration_seconds' => $firstSeen->diffInSeconds($lastSeen),
            'requests_count' => count($requests),

            /*
             * Mantiene orden y repeticiones para que User Journey
             * pueda reconstruir las pageviews correctamente.
             */
            'paths' => array_column($requests, 'path'),
            'requests' => $requests,

            'classification' => 'human_like',
            'activity_type' => 'normal',
            'risk_level' => 'clean',

            'reason' => 'Loaded the homepage, app assets, multiple pages, '
                . 'and submitted product analytics.',

            'reason_codes' => [
                'product_analytics_completed',
            ],

            'requested_home' => true,
            'loaded_assets' => true,
            'requested_sensitive_paths' => false,
            'sensitive_paths' => [],
        ];

        $summary = [
            'generated_at' => $baseTime->toIso8601String(),
            'total_requests' => count($requests),
            'total_sessions' => 1,
            'skipped_lines' => 0,
            'session_gap_minutes' => 30,

            'summary' => [
                'human_like' => 1,
                'scanner' => 0,
                'internal' => 0,
                'unknown' => 0,
            ],

            'sessions' => [
                $session,
            ],
        ];

        $disk = Storage::disk('local');

        /*
         * Archivo visible como access log sintético.
         */
        $disk->put(
            self::RAW_LOG_PATH,
            implode(
                PHP_EOL,
                array_map(
                    fn (array $request): string =>
                        $this->toCombinedLogLine($request),
                    $requests
                )
            ) . PHP_EOL
        );

        /*
         * Este es el archivo que TrafficSummaryReader consume.
         */
        $disk->put(
            self::SUMMARY_PATH,
            json_encode(
                $summary,
                JSON_PRETTY_PRINT
                    | JSON_UNESCAPED_SLASHES
                    | JSON_THROW_ON_ERROR
            )
        );
    }

    private function request(
        Carbon $occurredAt,
        string $method,
        string $path,
        int $status,
        int $bytes,
        string $ipAddress,
        string $userAgent,
        string $referrer,
    ): array {
        return [
            'ip' => $ipAddress,
            'occurred_at' => $occurredAt->toIso8601String(),
            'timestamp' => $occurredAt->timestamp,
            'method' => $method,
            'path' => $path,
            'protocol' => 'HTTP/2.0',
            'status' => $status,
            'bytes' => $bytes,
            'referrer' => $referrer,
            'user_agent' => $userAgent,
        ];
    }

    private function toCombinedLogLine(array $request): string
    {
        $occurredAt = Carbon::parse(
            $request['occurred_at'],
            'UTC'
        );

        return sprintf(
            '%s - - [%s] "%s %s %s" %d %d "%s" "%s"',
            $request['ip'],
            $occurredAt->format('d/M/Y:H:i:s O'),
            $request['method'],
            $request['path'],
            $request['protocol'],
            $request['status'],
            $request['bytes'],
            $request['referrer'],
            $request['user_agent'],
        );
    }
}