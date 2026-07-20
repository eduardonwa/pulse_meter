@php
    $highestStage = $session['highest_product_stage'] ?? 'none';
    $productEventsCount = (int) ($session['product_events_count'] ?? 0);
    $productDurationSeconds = (int) ($session['product_duration_seconds'] ?? 0);
    $pageviewsCount = (int) ($session['pageviews_count'] ?? 0);
    $productSessionsCount = count($session['product_sessions'] ?? []);
    $engaged = (bool) ($session['engaged'] ?? false);
    $engagementReasons = collect($session['engagement_reasons'] ?? []);
    $explorationContentId = $sessionId . '-exploration-content';
@endphp

<section class="product-session__exploration">
    <x-collapse-toggle
        label="Exploration"
        label-class="collapse-toggle__label"
        :controls="$explorationContentId"
        class="exploration__toggle"
    />

    <div class="product-session__exploration-content"
        id="{{ $explorationContentId }}"
        x-show="open"
        x-cloak
    >
        <div class="product-session__exploration-summary">
            <div class=" product-session__exploration-stage product-session__exploration-stage--{{ str($highestStage)->slug('-') }}">
                <div class="product-session__exploration-stage-content">
                    <x-heroicon-s-flag class="product-session__exploration-stage-icon" />

                    <span class="product-session__exploration-label">
                        Highest stage
                    </span>

                    <strong class="product-session__exploration-stage-value">
                        {{ str($highestStage)->replace('_', ' ')->title() }}
                    </strong>
                </div>
            </div>

            <div
                @class([
                    'product-session__exploration-engagement',
                    'product-session__exploration-engagement--success' => $engaged,
                    'product-session__exploration-engagement--neutral' => ! $engaged,
                ])
            >
                <div class="product-session__exploration-engagement-content">
                    <x-heroicon-s-light-bulb class="product-session__exploration-engagement-icon" />

                    <span class="product-session__exploration-label">
                        Engaged
                    </span>

                    <strong class="product-session__exploration-engagement-value">
                        {{ $engaged ? 'Yes' : 'No' }}
                    </strong>
                </div>
            </div>
        </div>

        <dl class="product-session__exploration-metrics">
            <div class="product-session__exploration-metric">
                <dt class="label">Pageviews</dt>
                <dd class="value">{{ $pageviewsCount }}</dd>
            </div>

            <div class="product-session__exploration-metric">
                <dt class="label">Product events</dt>
                <dd class="value">{{ $productEventsCount }}</dd>
            </div>

            <div class="product-session__exploration-metric">
                <dt class="label">Product sessions</dt>
                <dd class="value">{{ $productSessionsCount }}</dd>
            </div>

            <div class="product-session__exploration-metric">
                <dt class="label">Product activity</dt>
                <dd class="value">
                    {{ \App\Services\UserDateFormatter::duration(
                        $productDurationSeconds
                    ) }}
                </dd>
            </div>
        </dl>

        @if ($engagementReasons->isNotEmpty())
            <div class="product-session__exploration-reasons">
                <h3 class="product-session__exploration-reasons-title">
                    Engagement signals
                </h3>

                <ul class="product-session__exploration-reason-list">
                    @foreach ($engagementReasons as $reason)
                        <li class="product-session__exploration-reason">
                            {{ match ($reason) {
                                'multiple_pageviews' => 'Visited multiple pages',
                                'product_stage_exploration' => 'Explored the product',
                                'product_stage_trial' => 'Started trying the product',
                                'product_stage_activation' => 'Reached activation',
                                'product_activity_10_seconds' => 'Spent at least 10 seconds using the product',
                                default =>
                                    str($reason)
                                        ->replace('_', ' ')
                                        ->title(),
                            } }}
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</section>