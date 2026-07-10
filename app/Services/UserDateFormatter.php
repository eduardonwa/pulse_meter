<?php

namespace app\Services;

use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class UserDateFormatter
{
    public static function dateTime(string|CarbonInterface|null $value, ?User $user = null): string
    {
        if (blank($value)) {
            return '—';
        }

        $timezone = $user?->timezone ?: 'America/Hermosillo';

        $style = $user?->date_format_style ?: 'mx';

        $format = match ($style) {
            'us' => 'm/d/Y h:i A',
            default => 'd/m/Y h:i A',
        };

        return Carbon::parse($value)
            ->timezone($timezone)
            ->format($format);
    }

    public static function dateTimeWithTimezone(string|CarbonInterface|null $value, ?User $user = null): string
    {
        if (blank($value)) {
            return '—';
        }

        $timezone = $user?->timezone ?: 'America/Hermosillo';

        return self::dateTime($value, $user) . ' ' . $timezone;
    }

    public static function dateTimeParts(string|\Carbon\CarbonInterface|null $value, ?\App\Models\User $user = null): array
    {
        if (blank($value)) {
            return [
                'date' => '—',
                'time' => '—',
                'timezone' => $user?->timezone ?: 'America/Hermosillo',
            ];
        }

        $timezone = $user?->timezone ?: 'America/Hermosillo';

        $date = \Illuminate\Support\Carbon::parse($value)
            ->timezone($timezone)
            ->locale('es');

        return [
            'date' => $date->translatedFormat('j \d\e F, Y'),
            'time' => $date->format('h:i A'),
            'timezone' => $timezone,
        ];
    }
}