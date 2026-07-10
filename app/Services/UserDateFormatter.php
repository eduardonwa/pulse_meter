<?php

namespace app\Services;

use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class UserDateFormatter
{
    public static function timezone(?\App\Models\User $user = null): string
    {
        return $user?->timezone ?: 'America/Hermosillo';
    }

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

    public static function dateTimeParts(string|int|\Carbon\CarbonInterface|null $value, ?\App\Models\User $user = null): array
    {
        $timezone = self::timezone($user);

        if (blank($value)) {
            return [
                'date' => '—',
                'time' => '—',
                'timezone' => $timezone,
            ];
        }

        $date = is_int($value)
            ? \Illuminate\Support\Carbon::createFromTimestamp($value, 'UTC')
            : \Illuminate\Support\Carbon::parse($value);

        $date = $date
            ->timezone($timezone)
            ->locale('en');

        return [
            'date' => $date->translatedFormat('j F, Y'),
            'time' => $date->format('h:i A'),
            'timezone' => $timezone,
        ];
    }

    public static function duration(int|float|null $seconds): string
    {
        $seconds = (int) ($seconds ?? 0);

        if ($seconds < 60) {
            return $seconds === 1 ? '1 segundo' : "{$seconds} segundos";
        }

        $minutes = intdiv($seconds, 60);
        $remainingSeconds = $seconds % 60;

        if ($minutes < 60) {
            return $remainingSeconds > 0
                ? "{$minutes} min {$remainingSeconds} s"
                : "{$minutes} min";
        }

        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        return $remainingMinutes > 0
            ? "{$hours} h {$remainingMinutes} min"
            : "{$hours} h";
    }

    public static function dateOnly(string|null $value, ?\App\Models\User $user = null): string
    {
        if (blank($value)) {
            return '—';
        }

        $timezone = $user?->timezone ?: 'America/Hermosillo';

        return \Illuminate\Support\Carbon::createFromFormat('Y-m-d', $value, $timezone)
            ->locale('en')
            ->translatedFormat('j F, Y');
    }
}