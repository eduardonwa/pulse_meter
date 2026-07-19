@php
    $events = collect($productSessions)
        ->flatMap(
            fn (array $productSession) =>
                $productSession['events'] ?? []
        )
        ->sortBy('occurred_at')
        ->values();

    $eventTypeCounts = $events
        ->groupBy(
            fn (array $event): string =>
                $event['event_name'] ?? 'unknown_event'
        )
        ->map(
            fn ($eventGroup): int =>
                $eventGroup->count()
        );
@endphp

<div class="session-entry__product-usage" x-data="{ selectedEventTypes: [] }">
    <section class="product-session" x-data="{ selectedEventTypes: [] }">
        {{-- EXPLORATION --}}
        <div class="product-session__group" x-data="{ open: true, selectedEventTypes: [] }">
            @include('filament.pages.traffic-analytics.session.event-results.exploration')
        </div>

        {{-- USER JOURNEY --}}
        <div class="product-session__group" x-data="{ open: true, selectedEventTypes: [] }">
            @include('filament.pages.traffic-analytics.session.event-results.user-journey')
        </div>

        {{-- EVENTS --}}
        <div class="product-session__group" x-data="{ open: true, selectedEventTypes: [] }">
            @include('filament.pages.traffic-analytics.session.event-results.events')
        </div>

        {{-- SESSION DETAILS --}}
        <div class="product-session__group" x-data="{ open: false }">
            @include('filament.pages.traffic-analytics.session.event-results.details')
        </div>
    </section>
</div>