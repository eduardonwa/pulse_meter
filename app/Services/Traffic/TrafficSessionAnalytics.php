<?php

namespace App\Services\Traffic;

class TrafficSessionAnalytics
{
    public function __construct(
        private TrafficRequestInspector $requestInspector
    ) {
    }

    /**
     * Add navigation analytics to one request session.
     *
     * @param array<string, mixed> $session
     * @return array<string, mixed>
     */
    public function analyze(array $session): array
    {
        $requests = $session['requests'] ?? [];

        if (! is_array($requests)) { $requests = []; }

        $pagePaths = collect($requests)
            ->filter(
                fn (mixed $request): bool =>
                    is_array($request)
                    && $this->requestInspector
                        ->isSuccessfulPageRequest($request)
            )
            ->map(
                fn (array $request): string =>
                    $this->requestInspector->normalizePath(
                        (string) ($request['path'] ?? '')
                    )
            )
            ->filter(
                fn (string $path): bool =>
                    $path !== ''
            )
            ->values()
            ->all();

        $pageviewsCount = count($pagePaths);

        $productSessions = $session['product_sessions'] ?? [];

        if (! is_array($productSessions)) {
            $productSessions = [];
        }

        $productEventsCount = collect($productSessions)
            ->sum(
                fn (array $productSession): int =>
                    (int) ($productSession['events_count'] ?? 0)
            );

        $productDurationSeconds = (int) (
            collect($productSessions)
                ->map(
                    fn (array $productSession): int =>
                        (int) (
                            $productSession['duration_seconds'] ?? 0
                        )
                )
                ->max() ?? 0
        );

        $highestProductStage = $this->highestProductStage(
            $productSessions
        );

        $engagementReasons = [];

        if ($pageviewsCount >= 2) { $engagementReasons[] = 'multiple_pageviews'; }

        if (
            in_array(
                $highestProductStage,
                ['exploration', 'trial', 'activation'],
                true
            )
        ) {
            $engagementReasons[] =
                'product_stage_' . $highestProductStage;
        }

        if ($productDurationSeconds >= 10) {
            $engagementReasons[] =
                'product_activity_10_seconds';
        }

        $engaged = $engagementReasons !== [];

        $classification = (string) (
            $session['classification'] ?? 'unknown'
        );
        
        $bounceEligible =
            $classification === 'browser_like'
            && $pageviewsCount >= 1;

        $isBounce =
            $bounceEligible
            && $pageviewsCount === 1
            && ! $engaged;

        $bounceStatus = match (true) {
            ! $bounceEligible => 'ineligible',
            $isBounce => 'bounced',
            default => 'not_bounced',
        };

        $bounceReason = match (true) {
            $classification !== 'browser_like' => 'classification_not_eligible',
            $pageviewsCount === 0 => 'no_valid_pageview',
            $pageviewsCount >= 2 => 'multiple_pageviews',
            $engaged => 'single_page_with_engagement',
            default => 'single_page_without_engagement',
        };

        return array_merge($session, [
            'page_paths' => $pagePaths,
            'pageviews_count' => $pageviewsCount,
            'entrance_path' => $pagePaths[0] ?? null,
            'previous_path' =>
                $pageviewsCount >= 2
                    ? $pagePaths[$pageviewsCount - 2]
                    : null,

            'exit_path' =>
                $pageviewsCount >= 1
                    ? $pagePaths[$pageviewsCount - 1]
                    : null,

            'product_events_count' => $productEventsCount,
            'product_duration_seconds' => $productDurationSeconds,
            'highest_product_stage' => $highestProductStage,
            'engaged' => $engaged,
            'engagement_reasons' => $engagementReasons,
            'bounce_eligible' => $bounceEligible,
            'is_bounce' => $isBounce,
            'bounce_status' => $bounceStatus,
            'bounce_reason' => $bounceReason,
        ]);
    }

    /**
     * Add navigation analytics to multiple sessions.
     *
     * @param array<int, array<string, mixed>> $sessions
     * @return array<int, array<string, mixed>>
     */
    public function analyzeMany(array $sessions): array
    {
        return collect($sessions)
            ->map(
                fn (array $session): array =>
                    $this->analyze($session)
            )
            ->all();
    }

    /**
     * Build a bounce-rate summary from analyzed sessions.
     *
     * @param array<int, array<string, mixed>> $sessions
     * @return array<string, int|float|null>
     */
    public function summarize(array $sessions): array
    {
        $eligibleSessions = collect($sessions)
            ->filter(
                fn (array $session): bool =>
                    ($session['bounce_eligible'] ?? false) === true
            )
            ->values();

        $eligibleSessionsCount = $eligibleSessions->count();

        $bouncedSessionsCount = $eligibleSessions
            ->filter(
                fn (array $session): bool =>
                    ($session['is_bounce'] ?? false) === true
            )
            ->count();

        $notBouncedSessionsCount =
            $eligibleSessionsCount - $bouncedSessionsCount;

        $singlePageSessionsCount = $eligibleSessions
            ->filter(
                fn (array $session): bool =>
                    (int) ($session['pageviews_count'] ?? 0) === 1
            )
            ->count();

        $singlePageEngagedSessionsCount = $eligibleSessions
            ->filter(
                fn (array $session): bool =>
                    (int) ($session['pageviews_count'] ?? 0) === 1
                    && ($session['engaged'] ?? false) === true
            )
            ->count();

        $bounceRate = $eligibleSessionsCount > 0
            ? round(
                ($bouncedSessionsCount / $eligibleSessionsCount) * 100,
                2
            )
            : null;

        return [
            'eligible_sessions' => $eligibleSessionsCount,
            'bounced_sessions' => $bouncedSessionsCount,
            'not_bounced_sessions' => $notBouncedSessionsCount,
            'single_page_sessions' => $singlePageSessionsCount,
            'single_page_engaged_sessions' => $singlePageEngagedSessionsCount,
            'bounce_rate' => $bounceRate,
        ];
    }

    /**
     * Return the most advanced product stage found
     * in the correlated product sessions.
     *
     * @param array<int, array<string, mixed>> $productSessions
     */
    private function highestProductStage(
        array $productSessions
    ): string {
        $weights = [
            'none' => 0,
            'visit' => 1,
            'exploration' => 2,
            'trial' => 3,
            'activation' => 4,
        ];

        return collect($productSessions)
            ->pluck('highest_stage')
            ->filter(
                fn (mixed $stage): bool =>
                    is_string($stage)
                    && $stage !== ''
            )
            ->unique()
            ->sortByDesc(
                fn (string $stage): int =>
                    $weights[$stage] ?? 0
            )
            ->first() ?? 'none';
    }
}