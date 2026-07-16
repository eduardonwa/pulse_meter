<div class="session-entry__product-usage">
    @forelse ($productSessions as $productSession)
        @php
            $events = collect($productSession['events'] ?? []);

            $eventTypeCounts = $events->groupBy(
                fn (array $event): string =>
                    $event['event_name'] ?? 'unknown_event'
                )
                ->map(fn ($eventGroup): int => $eventGroup->count());
        @endphp
        <section class="product-session" x-data="{ selectedEventTypes: []}">
            <header class="product-session__header">
                <div class="field">
                    <span class="label"> Visitor ID </span>
                    <p class="value"> {{ $productSession['visitor_id'] ?? 'Unknown visitor' }} </p>
                </div>

                <div class="field">
                    <span class="label"> Product session </span>
                    <p class="value"> {{ $productSession['session_id'] ?? 'Unknown session' }} </p>
                </div>

                <div class="field">
                    <span class="label"> Highest stage </span>
                    <p class="value">
                        {{
                            str($productSession['highest_stage'] ?? 'visit')
                                ->replace('_', ' ')
                                ->title()
                        }}
                    </p>
                </div>
            </header>

            @include('filament.pages.traffic-analytics.session.event-results.header', [
                'events' => $events,
                'eventTypeCounts' => $eventTypeCounts,
            ])

            @include('filament.pages.traffic-analytics.session.event-results.events', [
                'events' => $events,
                'productSession' => $productSession,
            ])
        </section>
    @empty
        <p class="session-entry__empty-message">
            No product events were matched to this visit.
        </p>
    @endforelse
</div>