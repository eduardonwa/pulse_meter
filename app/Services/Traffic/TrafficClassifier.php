<?php

namespace App\Services\Traffic;

use Illuminate\Support\Str;

class TrafficClassifier
{
    private array $internalIps;

    public function __construct()
    {
        $this->internalIps = config('traffic.internal_ips', ['127.0.0.1']);
    }

    private array $adminPathStartsWith = [
        '/admin',
        '/js/filament/',
        '/css/filament/',
        '/fonts/filament/',
    ];

    private array $sensitivePathStartsWith = [
        '/.env',
        '/.aws',
        '/.git',
        '/.stripe',
        '/.docker',
        '/.vscode',
        '/.cache',
        '/wp-login.php',
        '/wp-admin',
        '/xmlrpc.php',
        '/phpmyadmin',
        '/pma',
        '/adminer',
    ];

    private array $sensitivePathContains = [
        '..\\',
        '/../',
        'var/log/',
        'access.log',
        'credentials',
        'secrets',
        'config.json',
        'sftp.json',
        '.amplifyrc',
        '.boto',
        '.dockerignore',
    ];

    private array $appAssetStartsWith = [
        '/build/assets/',
        '/audio/click-profiles/',
    ];

    private array $knownAppAssets = [
        '/favicon.ico',
        '/click.wav',
        '/accent.wav',
    ];

    private array $appAssetPatterns = [
        '/^\/main-[a-z0-9_-]+\.css$/i',
        '/^\/app-[a-z0-9_-]+\.js$/i',
        '/^\/inter-variablefont[^\/]*\.woff2$/i',
    ];

    public function classify(array $requests): array
    {
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

        $loadedAssets = $paths->contains(function ($path) {
            return $this->isAppAsset($path);
        });

        $sensitivePaths = $paths
            ->filter(fn ($path) => $this->isSensitivePath($path))
            ->unique()
            ->values();

        $hasSensitiveRequest = $sensitivePaths->isNotEmpty();

        $hasBlockedStatus = $statuses->contains(444);

        $reasonCodes = [];

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

        if ($requestedHome && $loadedAssets) {
            return [
                'classification' => 'browser_like',
                'confidence' => 'possible',
                'risk_level' => 'clean',
                'reason' => 'Loaded homepage and app assets',
                'reason_codes' => [
                    'loaded_homepage',
                    'loaded_app_assets',
                ],
                'requests_count' => $requestsCount,
                'requested_home' => true,
                'loaded_assets' => true,
                'requested_sensitive_paths' => false,
                'sensitive_paths' => [],
            ];
        }

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

    private function isSensitivePath(string $path): bool
    {
        $normalizedPath = $this->normalizePath($path);

        foreach ($this->sensitivePathStartsWith as $pattern) {
            if (Str::startsWith($normalizedPath, strtolower($pattern))) {
                return true;
            }
        }

        foreach ($this->sensitivePathContains as $pattern) {
            if (Str::contains($normalizedPath, strtolower($pattern))) {
                return true;
            }
        }

        return false;
    }

    private function isAppAsset(string $path): bool
    {
        $normalizedPath = $this->normalizePath($path);

        foreach ($this->knownAppAssets as $asset) {
            if ($normalizedPath === strtolower($asset)) {
                return true;
            }
        }

        foreach ($this->appAssetStartsWith as $prefix) {
            if (Str::startsWith($normalizedPath, strtolower($prefix))) {
                return true;
            }
        }

        foreach ($this->appAssetPatterns as $pattern) {
            if (preg_match($pattern, $normalizedPath) === 1) {
                return true;
            }
        }

        return false;
    }

    private function requestedAdminArea(array $requests): bool
    {
        foreach ($requests as $request) {
            $path = $this->normalizePath($request['path'] ?? '');

            foreach ($this->adminPathStartsWith as $adminPath) {
                if (Str::startsWith($path, strtolower($adminPath))) {
                    return true;
                }
            }
        }

        return false;
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

    private function normalizePath(string $path): string
    {
        $path = explode('?', $path, 2)[0];
        $path = explode('#', $path, 2)[0];

        return strtolower(urldecode($path));
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