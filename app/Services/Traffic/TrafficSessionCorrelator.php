<?php

namespace App\Services\Traffic;

use Illuminate\Support\Carbon;

class TrafficSessionCorrelator
{
    /**
     * Attach matching product sessions to each request session.
     *
     * @param array<int, array<string, mixed>> $requestSessions
     * @param array<int, array<string, mixed>> $productSessions
     * @return array<int, array<string, mixed>>
     */
    public function correlate(
        array $requestSessions,
        array $productSessions,
        int $toleranceSeconds = 60
    ): array {
        return collect($requestSessions)
            ->map(function (array $requestSession) use (
                $productSessions,
                $toleranceSeconds
            ): array {
                $matches = collect($productSessions)
                    ->filter(
                        fn (array $productSession): bool =>
                            $this->matches(
                                $requestSession,
                                $productSession,
                                $toleranceSeconds
                            )
                    )
                    ->values()
                    ->all();

                $requestSession['product_sessions'] = $matches;

                $requestSession['product_events_count'] =
                    collect($matches)->sum(
                        fn (array $session): int =>
                            (int) ($session['events_count'] ?? 0)
                    );

                return $requestSession;
            })
            ->all();
    }

    private function matches(
        array $requestSession,
        array $productSession,
        int $toleranceSeconds
    ): bool {
        $requestIp = trim(
            (string) ($requestSession['ip'] ?? '')
        );

        $productIp = trim(
            (string) ($productSession['ip_address'] ?? '')
        );

        if ($requestIp === '' || $requestIp !== $productIp) {
            return false;
        }

        $requestUserAgent = trim(
            (string) ($requestSession['user_agent'] ?? '')
        );

        $productUserAgent = trim(
            (string) ($productSession['user_agent'] ?? '')
        );

        if (
            $requestUserAgent === ''
            || $requestUserAgent !== $productUserAgent
        ) {
            return false;
        }

        $requestStart = $this->requestTimestamp(
            $requestSession,
            'first_seen_timestamp',
            'first_seen'
        );

        $requestEnd = $this->requestTimestamp(
            $requestSession,
            'last_seen_timestamp',
            'last_seen'
        );

        $productStart = isset(
            $productSession['first_event_timestamp']
        )
            ? (int) $productSession['first_event_timestamp']
            : null;

        $productEnd = isset(
            $productSession['last_event_timestamp']
        )
            ? (int) $productSession['last_event_timestamp']
            : null;

        if (
            $requestStart === null
            || $requestEnd === null
            || $productStart === null
            || $productEnd === null
        ) {
            return false;
        }

        return $productStart <= ($requestEnd + $toleranceSeconds)
            && $productEnd >= ($requestStart - $toleranceSeconds);
    }

    private function requestTimestamp(
        array $session,
        string $timestampKey,
        string $dateKey
    ): ?int {
        if (
            isset($session[$timestampKey])
            && is_numeric($session[$timestampKey])
        ) {
            return (int) $session[$timestampKey];
        }

        $value = $session[$dateKey] ?? null;

        if (empty($value)) {
            return null;
        }

        return Carbon::parse($value)->timestamp;
    }
}