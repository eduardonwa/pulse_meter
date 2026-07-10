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
        '..%5c',
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

    private array $appAssetEndsWith = [
        '.css',
        '.js',
        '.woff2',
        '.wav',
        '.ico',
        '.svg',
    ];

    public function classify(array $requests): array
    {
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

        $requestsCount = count($requests);

        if ($this->isInternalIp($ip)) {
            return [
                'classification' => 'internal',
                'risk_level' => 'ignored',
                'reason' => 'Internal or excluded IP',
                'requests_count' => $requestsCount,
                'requested_home' => $requestedHome,
                'loaded_assets' => $loadedAssets,
                'requested_sensitive_paths' => $hasSensitiveRequest,
                'sensitive_paths' => $sensitivePaths->all(),
            ];
        }

        if ($hasSensitiveRequest && ($requestedHome || $loadedAssets)) {
            return [
                'classification' => 'suspicious',
                'risk_level' => 'suspicious',
                'reason' => 'Loaded normal app content but also requested sensitive paths',
                'requests_count' => $requestsCount,
                'requested_home' => $requestedHome,
                'loaded_assets' => $loadedAssets,
                'requested_sensitive_paths' => true,
                'sensitive_paths' => $sensitivePaths->all(),
            ];
        }
        
        if ($hasSensitiveRequest || $hasBlockedStatus) {
            return [
                'classification' => 'scanner',
                'risk_level' => 'blocked',
                'reason' => 'Requested sensitive paths or received 444',
                'requests_count' => $requestsCount,
                'requested_home' => $requestedHome,
                'loaded_assets' => $loadedAssets,
                'requested_sensitive_paths' => $hasSensitiveRequest,
                'sensitive_paths' => $sensitivePaths->all(),
            ];
        }

        if ($requestedHome && $loadedAssets) {
            return [
                'classification' => 'human_probable',
                'risk_level' => 'clean',
                'reason' => 'Loaded homepage and app assets',
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
            'reason' => 'Not enough evidence',
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
        $normalizedPath = strtolower(urldecode($path));

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
        $normalizedPath = strtolower($path);

        foreach ($this->appAssetStartsWith as $pattern) {
            if (Str::startsWith($normalizedPath, strtolower($pattern))) {
                return true;
            }
        }

        foreach ($this->appAssetEndsWith as $extension) {
            if (Str::endsWith($normalizedPath, strtolower($extension))) {
                return true;
            }
        }

        return false;
    }
}