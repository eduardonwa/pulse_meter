<?php

namespace App\Services\Traffic;

use App\Models\ProductEvent;
use App\Models\User;
use App\Services\UserDateFormatter;
use Illuminate\Support\Carbon;

class ProductEventSessionReader
{
     /**
     * Return dates that contain at least one product event,
     * formatted in the Filament user's timezone.
     *
     * @return array<int, string>
     */
    public function availableDates(?User $user): array
    {
        $timezone = UserDateFormatter::timezone($user);

        return ProductEvent::query()
            ->select('occurred_at')
            ->orderByDesc('occurred_at')
            ->get()
            ->map(
                fn (ProductEvent $event): string =>
                    $event->occurred_at
                        ->copy()
                        ->timezone($timezone)
                        ->toDateString()
            )
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Return product events grouped by browser session.
     *
     * @return array<int, array<string, mixed>>
     */
    public function read(
        ?string $date,
        ?User $user
    ): array {
        if ($date === null) {
            return [];
        }

        $userTimezone = UserDateFormatter::timezone($user);
        $applicationTimezone = config('app.timezone', 'UTC');

        /*
         * selectedSessionDate represents a calendar date in the
         * Filament user's timezone.
         *
         * Convert that local-day window to the timezone used by Laravel
         * when storing occurred_at.
         */
        $start = Carbon::createFromFormat(
            'Y-m-d H:i:s',
            "{$date} 00:00:00",
            $userTimezone
        )->setTimezone($applicationTimezone);

        $end = $start->copy()->addDay();

        $events = ProductEvent::query()
            ->where('occurred_at', '>=', $start)
            ->where('occurred_at', '<', $end)
            ->orderBy('occurred_at')
            ->get();

        return $events
            ->groupBy('session_id')
            ->map(function ($sessionEvents, string $sessionId): array {
                $sessionEvents = $sessionEvents
                    ->sortBy('occurred_at')
                    ->values();

                /** @var ProductEvent|null $firstEvent */
                $firstEvent = $sessionEvents->first();

                /** @var ProductEvent|null $lastEvent */
                $lastEvent = $sessionEvents->last();

                $firstOccurredAt = $firstEvent?->occurred_at;
                $lastOccurredAt = $lastEvent?->occurred_at;

                return [
                    'session_id' => $sessionId,
                    'visitor_id' => $firstEvent?->visitor_id,

                    'ip_address' => $sessionEvents
                        ->pluck('ip_address')
                        ->filter()
                        ->first(),

                    'user_agent' => $sessionEvents
                        ->pluck('user_agent')
                        ->filter()
                        ->first(),

                    'first_event_at' => $firstOccurredAt,
                    'last_event_at' => $lastOccurredAt,

                    'first_event_timestamp' =>
                        $firstOccurredAt?->timestamp,

                    'last_event_timestamp' =>
                        $lastOccurredAt?->timestamp,

                    'duration_seconds' =>
                        $firstOccurredAt && $lastOccurredAt
                            ? $firstOccurredAt->diffInSeconds(
                                $lastOccurredAt
                            )
                            : 0,

                    'events_count' => $sessionEvents->count(),

                    'highest_stage' => $this->highestStage(
                        $sessionEvents
                            ->pluck('stage')
                            ->filter()
                            ->all()
                    ),

                    'events' => $sessionEvents
                        ->map(
                            fn (ProductEvent $event): array => [
                                'event_id' => $event->event_id,
                                'event_name' => $event->event_name,
                                'stage' => $event->stage,
                                'properties' =>
                                    $event->properties ?? [],
                                'path' => $event->path,
                                'occurred_at' =>
                                    $event->occurred_at,
                                'occurred_at_timestamp' =>
                                    $event->occurred_at?->timestamp,
                            ]
                        )
                        ->all(),
                ];
            })
            ->sortByDesc('last_event_timestamp')
            ->values()
            ->all();
    }

    /**
     * Return the most advanced stage reached during the session.
     *
     * @param array<int, string> $stages
     */
    private function highestStage(array $stages): string
    {
        $weights = [
            'visit' => 1,
            'exploration' => 2,
            'trial' => 3,
            'activation' => 4,
        ];

        return collect($stages)
            ->unique()
            ->sortByDesc(
                fn (string $stage): int =>
                    $weights[$stage] ?? 0
            )
            ->first() ?? 'visit';
    }
}