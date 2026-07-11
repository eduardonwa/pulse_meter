<?php

namespace App\Services\Traffic;

use Illuminate\Support\Facades\Storage;

class TrafficSummaryReader
{
    private const PATH = 'traffic/traffic-summary.json';

    public function read(): array
    {
        $disk = Storage::disk('local');

        if (! $disk->exists(self::PATH)) {
            return [];
        }

        $decoded = json_decode(
            $disk->get(self::PATH),
            true
        );

        return is_array($decoded) ? $decoded : [];
    }
}