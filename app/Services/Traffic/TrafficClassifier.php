<?php

namespace App\Services\Traffic;

use Illuminate\Support\Str;

class TrafficClassifier
{
    private array $internalIps;

    private TrafficRequestInspector $requestInspector;

    public function __construct(
        TrafficRequestInspector $requestInspector
    ) {
        $this->requestInspector = $requestInspector;   

        $this->internalIps = config(
            'traffic.internal_ips',
            ['127.0.0.1']
        );
    }

    public function classify(array $requests): array
    {
        $reasonCodes = [];

        $userAgents = collect($requests)
            ->pluck('user_agent')
            ->filter()
            ->unique()
            ->values();

        $rapidDeviceSwitch = $this->detectRapidDeviceSwitch(
            $requests,
            windowSeconds: 10
        );

        $hasRapidDeviceSwitch = $rapidDeviceSwitch !== null;

        $sentProductAnalytics = collect($requests)->contains(function ($request) {
            return strtoupper($request['method'] ?? '') === 'POST'
                && ($request['path'] ?? null) === '/analytics/events'
                && (int) ($request['status'] ?? 0) === 204;
        });

        if ($userAgents->count() >= 2) {
            $reasonCodes[] = 'multiple_user_agents';
        }

        if ($hasRapidDeviceSwitch) {
            $reasonCodes[] = 'rapid_incompatible_user_agent_switch';
        }

        if ($sentProductAnalytics) {
            $reasonCodes[] = 'product_analytics_completed';
        }

        $hasUrlAsUserAgent = $userAgents->contains(function ($userAgent) {
            return filter_var(
                $userAgent,
                FILTER_VALIDATE_URL
            ) !== false;
        });

        $paths = collect($requests)
            ->pluck('path')
            ->filter()
            ->values();

        $statuses = collect($requests)
            ->pluck('status')
            ->filter()
            ->map(fn ($status) => (int) $status)
            ->values();

        $ip = $requests[0]['ip'] ?? null;

        $requestedHome = collect($requests)->contains(function ($request) {
            return ($request['path'] ?? null) === '/'
                && (int) ($request['status'] ?? 0) === 200;
        });

        $requestedPage = collect($requests)->contains(
            fn (array $request): bool =>
                $this->requestInspector
                    ->isSuccessfulPageRequest($request)
        );

        $loadedAssets = $paths->contains(
            fn (string $path): bool =>
                $this->requestInspector->isAppAsset($path)
        );

        $sensitivePaths = $paths
            ->filter(
                fn (string $path): bool =>
                    $this->requestInspector
                        ->isSensitivePath($path)
            )
            ->unique()
            ->values();

        $hasSensitiveRequest = $sensitivePaths->isNotEmpty();

        $hasBlockedStatus = $statuses->contains(444);

        if ($hasSensitiveRequest) {
            $reasonCodes[] = 'requested_sensitive_path';
        }

        if ($hasBlockedStatus) {
            $reasonCodes[] = 'received_444';
        }

        if ($hasUrlAsUserAgent) {
            $reasonCodes[] = 'url_used_as_user_agent';
        }

        $requestsCount = count($requests);
        
        // INTERAL
        if ($this->isInternalIp($ip)) {
            return [
                'classification' => 'internal',
                'activity_type' => $this->requestedAdminArea($requests)
                    ? 'admin'
                    : 'normal',
                'risk_level' => 'ignored',
                'reason' => 'Internal or excluded IP',
                'reason_codes' => ['internal_ip'],
                'requests_count' => $requestsCount,
                'requested_home' => $requestedHome,
                'loaded_assets' => $loadedAssets,
                'requested_sensitive_paths' => $hasSensitiveRequest,
                'sensitive_paths' => $sensitivePaths->all(),
            ];
        }
        
        // SCANNER
        if ($hasSensitiveRequest || $hasBlockedStatus) {
            return [
                'classification' => 'scanner',
                'risk_level' => $hasBlockedStatus
                    ? 'blocked'
                    : 'high',
                'reason' => $this->buildReasonText(
                    $hasSensitiveRequest,
                    $hasBlockedStatus,
                    $hasUrlAsUserAgent
                ),
                'reason_codes' => $reasonCodes,
                'requests_count' => $requestsCount,
                'requested_home' => $requestedHome,
                'loaded_assets' => $loadedAssets,
                'requested_sensitive_paths' => $hasSensitiveRequest,
                'sensitive_paths' => $sensitivePaths->all(),
                'behavior' => ($requestedHome || $loadedAssets)
                    ? 'mixed'
                    : 'scanner_only',
            ];
        }

        // AUTOMATION
        if ($hasRapidDeviceSwitch) {
            return [
                'classification' => 'automation_suspected',
                'activity_type' => 'browser_automation',
                'confidence' => 'high',

                'risk_level' => 'low',

                'reason' => sprintf(
                    'Same IP switched from %s/%s to %s/%s within %d seconds',
                    $rapidDeviceSwitch['from']['platform'],
                    $rapidDeviceSwitch['from']['browser'],
                    $rapidDeviceSwitch['to']['platform'],
                    $rapidDeviceSwitch['to']['browser'],
                    $rapidDeviceSwitch['seconds']
                ),

                'reason_codes' => $reasonCodes,
                
                'requests_count' => $requestsCount,
                'requested_home' => $requestedHome,
                'loaded_assets' => $loadedAssets,
                'sent_product_analytics' => $sentProductAnalytics,

                'user_agents_count' => $userAgents->count(),
                'user_agent_switch' => $rapidDeviceSwitch,

                'requested_sensitive_paths' => false,
                'sensitive_paths' => []
            ];
        }

        // BROWSER
        if ($requestedPage && ($loadedAssets || $sentProductAnalytics)) {
            $browserReasonCodes = $reasonCodes;

            $browserReasonCodes[] = 'requested_application_page';

            if ($loadedAssets) {
                $browserReasonCodes[] = 'loaded_app_assets';
            }

            if ($sentProductAnalytics) {
                $browserReasonCodes[] = 'product_analytics_completed';
            }

            return [
                'classification' => 'browser_like',

                'confidence' => $sentProductAnalytics
                    ? 'probable'
                    : 'possible',

                'risk_level' => 'clean',

                'reason' => $sentProductAnalytics
                    ? 'Requested an application page and sent product analytics'
                    : 'Requested an application page and loaded app assets',

                'reason_codes' => array_values(array_unique($browserReasonCodes)),

                'requests_count' => $requestsCount,

                'requested_home' => $requestedHome,
                'requested_page' => true,
                'loaded_assets' => $loadedAssets,

                'sent_product_analytics' => $sentProductAnalytics,

                'requested_sensitive_paths' => false,
                'sensitive_paths' => [],
            ];
        }

        // UNKNOWN
        return [
            'classification' => 'unknown',
            'risk_level' => 'neutral',
            'reason' => $hasUrlAsUserAgent
                ? 'Unusual User-Agent but not enough evidence'
                : 'Not enough evidence',
            'reason_codes' => $reasonCodes,
            'requests_count' => $requestsCount,
            'requested_home' => $requestedHome,
            'loaded_assets' => $loadedAssets,
            'requested_sensitive_paths' => $hasSensitiveRequest,
            'sensitive_paths' => $sensitivePaths->all(),
        ];
    }
    
    private function isInternalIp(?string $ip): bool
    {
        if ($ip === null) {
            return false;
        }

        return in_array($ip, $this->internalIps, true);
    }

    private function requestedAdminArea(array $requests): bool
    {
        return collect($requests)->contains(
            fn (array $request): bool =>
                $this->requestInspector->isAdminPath(
                    (string) ($request['path'] ?? '')
                )
        );
    }

    private function buildReasonText(
        bool $hasSensitiveRequest,
        bool $hasBlockedStatus,
        bool $hasUrlAsUserAgent,
    ): string {
        $reasons = [];

        if ($hasSensitiveRequest) {
            $reasons[] = 'requested sensitive paths';
        }

        if ($hasBlockedStatus) {
            $reasons[] = 'received 444';
        }

        if ($hasUrlAsUserAgent) {
            $reasons[] = 'used a URL as the User-Agent';
        }

        if ($reasons === []) {
            return 'Not enough evidence';
        }

        return ucfirst(implode(', ', $reasons));
    }

    private function detectRapidDeviceSwitch(
        array $requests,
        int $windowSeconds = 10
    ): ?array {
        $timeline = collect($requests)
            ->map(function ($request) {
                $userAgent = $request['user_agent'] ?? null;

                $timestamp = $request['timestamp']
                    ?? $request['occurred_at']
                    ?? $request['requested_at']
                    ?? null;

                if (!$userAgent || !$timestamp) { return null; }

                try {
                    $unixTimestamp = \Carbon\CarbonImmutable::parse(
                        $timestamp
                    )->getTimestamp();
                } catch (\Throwable) {
                    return null;
                }

                return [
                    'timestamp' => $unixTimestamp,
                    'user_agent' => $userAgent,
                    'profile' => $this->parseUserAgentProfile(
                        $userAgent
                    ),
                ];
            })
            ->filter()
            ->sortBy('timestamp')
            ->values();

        for ($index = 1; $index < $timeline->count(); $index++) {
            $previous = $timeline[$index - 1];
            $current = $timeline[$index];

            if (
                $previous['user_agent']
                === $current['user_agent']
            ) {
                continue;
            }

            $seconds = $current['timestamp']
                - $previous['timestamp'];

            if ($seconds < 0 || $seconds > $windowSeconds) {
                continue;
            }

            $from = $previous['profile'];
            $to = $current['profile'];

            $knownPlatforms = $from['platform'] !== 'unknown'
                && $to['platform'] !== 'unknown';

            $incompatiblePlatforms = $knownPlatforms
                && $from['platform'] !== $to['platform'];

            if (!$incompatiblePlatforms) {
                continue;
            }

            return [
                'seconds' => $seconds,

                'from' => [
                    'platform' => $from['platform'],
                    'browser' => $from['browser'],
                    'user_agent' => $previous['user_agent'],
                ],

                'to' => [
                    'platform' => $to['platform'],
                    'browser' => $to['browser'],
                    'user_agent' => $current['user_agent'],
                ],
            ];
        }

        return null;
    }

    private function parseUserAgentProfile(
    string $userAgent
    ): array {
        $platform = match (true) {
            str_contains($userAgent, 'iPhone'),
            str_contains($userAgent, 'iPad') => 'ios',

            str_contains($userAgent, 'Android') => 'android',

            str_contains($userAgent, 'Windows NT') => 'windows',

            str_contains($userAgent, 'Macintosh') => 'macos',

            str_contains($userAgent, 'Linux') => 'linux',

            default => 'unknown',
        };

        $browser = match (true) {
            str_contains($userAgent, 'EdgA/') => 'edge_android',

            str_contains($userAgent, 'EdgiOS/') => 'edge_ios',

            str_contains($userAgent, 'Edg/') => 'edge',

            str_contains($userAgent, 'CriOS/') => 'chrome_ios',

            str_contains($userAgent, 'Chrome/') => 'chrome',

            str_contains($userAgent, 'Firefox/')
                || str_contains($userAgent, 'FxiOS/') => 'firefox',

            str_contains($userAgent, 'Safari/')
                && str_contains($userAgent, 'Version/') => 'safari',

            default => 'unknown',
        };

        return [
            'platform' => $platform,
            'browser' => $browser,
        ];
    }
}