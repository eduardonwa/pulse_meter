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

        if ($requestedHome && $loadedAssets) {
            return [
                'classification' => 'human_like',
                'confidence' => 'likely',
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
}