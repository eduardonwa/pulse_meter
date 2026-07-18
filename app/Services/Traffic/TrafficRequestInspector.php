<?php

namespace App\Services\Traffic;

use Illuminate\Support\Str;

class TrafficRequestInspector
{
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
        '/favicon.svg',
        '/robots.txt',
        '/click.wav',
        '/accent.wav',
    ];

    private array $appAssetPatterns = [
        '/^\/main-[a-z0-9_-]+\.css$/i',
        '/^\/app-[a-z0-9_-]+\.js$/i',
        '/^\/inter-variablefont[^\/]*\.woff2$/i',
        '/\.(?:css|js|mjs|map|png|jpe?g|gif|webp|avif|svg|ico|woff2?|ttf|otf|eot|mp3|wav|ogg|mp4|webm|pdf|xml|txt)$/i',
    ];

    public function isSuccessfulPageRequest(array $request): bool
    {
        $method = strtoupper(
            (string) ($request['method'] ?? '')
        );

        $status = (int) ($request['status'] ?? 0);

        $path = (string) ($request['path'] ?? '');

        if ($method !== 'GET') {
            return false;
        }

        if (! in_array($status, [200, 304], true)) {
            return false;
        }

        if ($path === '') {
            return false;
        }

        if ($this->isAppAsset($path)) {
            return false;
        }

        if ($this->isSensitivePath($path)) {
            return false;
        }

        if ($this->isAdminPath($path)) {
            return false;
        }

        return true;
    }

    public function normalizePath(string $path): string
    {
        $path = explode('?', $path, 2)[0];
        $path = explode('#', $path, 2)[0];

        return strtolower(urldecode($path));
    }

    public function isAppAsset(string $path): bool
    {
        $normalizedPath = $this->normalizePath($path);

        if (
            in_array(
                $normalizedPath,
                $this->knownAppAssets,
                true
            )
        ) {
            return true;
        }

        foreach ($this->appAssetStartsWith as $prefix) {
            if (
                Str::startsWith(
                    $normalizedPath,
                    strtolower($prefix)
                )
            ) {
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

    public function isSensitivePath(string $path): bool
    {
        $normalizedPath = $this->normalizePath($path);

        foreach ($this->sensitivePathStartsWith as $pattern) {
            if (
                Str::startsWith(
                    $normalizedPath,
                    strtolower($pattern)
                )
            ) {
                return true;
            }
        }

        foreach ($this->sensitivePathContains as $pattern) {
            if (
                Str::contains(
                    $normalizedPath,
                    strtolower($pattern)
                )
            ) {
                return true;
            }
        }

        return false;
    }

    public function isAdminPath(string $path): bool
    {
        $normalizedPath = $this->normalizePath($path);

        foreach ($this->adminPathStartsWith as $adminPath) {
            if (
                Str::startsWith(
                    $normalizedPath,
                    strtolower($adminPath)
                )
            ) {
                return true;
            }
        }

        return false;
    }
}