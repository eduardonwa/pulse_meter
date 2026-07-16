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

            <div class="product-session__events-header">
                <div class="product-session__events-summary">
                    <p class="product-session__events-description">
                        Session Events
                    </p>
                </div>

                <div class="product-session__event-filters" role="group" aria-label="Filter events in this product session">
                    <button class="product-session__event-filter"
                        type="button"
                        @click="selectedEventTypes = []"
                        :aria-pressed="
                            selectedEventTypes.length === 0
                                ? 'true'
                                : 'false'
                        "
                        :class="{ 'product-session__event-filter--active': selectedEventTypes.length === 0}"
                    >
                        All ({{ $events->count() }})
                    </button>

                    @foreach ($eventTypeCounts as $eventName => $eventCount)
                        <button class="product-session__event-filter"
                            type="button"
                            @click="
                                selectedEventTypes.includes(
                                    @js($eventName)
                                )
                                    ? selectedEventTypes =
                                        selectedEventTypes.filter(
                                            eventType =>
                                                eventType
                                                !== @js($eventName)
                                        )
                                    : selectedEventTypes.push(
                                        @js($eventName)
                                    )
                            "
                            :aria-pressed="
                                selectedEventTypes.includes(
                                    @js($eventName)
                                )
                                    ? 'true'
                                    : 'false'
                            "
                            :class="{
                                'product-session__event-filter--active':
                                    selectedEventTypes.includes(
                                        @js($eventName)
                                    )
                            }"
                        >
                            {{ str($eventName)->replace('_', ' ')->title() }}

                            <span class="product-session__event-filter-count">
                                ({{ $eventCount }})
                            </span>
                        </button>
                    @endforeach
                </div>
            </div>

            <ol class="product-session__events">
                @foreach ( $events as $event)
                    @php
                        $eventName = $event['event_name'] ?? 'unknown_event';
                        $eventTime =
                            \App\Services\UserDateFormatter::dateTimeParts(
                                $event['occurred_at'] ?? null,
                                auth()->user()
                            );
                    @endphp

                    <li class="event-entry" x-show="selectedEventTypes.length === 0 || selectedEventTypes.includes(@js($eventName))">
                        <header class="event-entry__header">
                            <h3 class="event-entry__name">
                                {{
                                    str(
                                        $event['event_name']
                                        ?? 'unknown_event'
                                    )
                                        ->replace('_', ' ')
                                        ->title()
                                }}
                            </h3>

                            <span class="event-entry__stage">
                                {{
                                    str(
                                        $event['stage']
                                        ?? 'visit'
                                    )
                                        ->replace('_', ' ')
                                        ->title()
                                }}
                            </span>

                            <time class="event-entry__time"> {{ $eventTime['time'] ?? '—' }} </time>
                        </header>

                        @if (! empty($event['properties']))
                            <dl class="event-entry__properties">
                                @foreach ($event['properties'] as $property => $value)
                                    @php
                                        $propertyLabel = match ($property) {
                                            'bpm' => 'BPM',
                                            'mode' => 'Form mode',
                                            'source' => 'Source',
                                            'exercise_mode' => 'Exercise mode',
                                            'exercise_count' => 'Exercise count',
                                            'exercise_index' => 'Exercise index',
                                            'exercise_origin' => 'Exercise origin',
                                            'previous_origin' => 'Previous origin',
                                            'duration_seconds' => 'Duration',
                                            'configured_duration_seconds' => 'Configured duration',
                                            'threshold_seconds' => 'Engagement threshold',
                                            'time_open_seconds' => 'Time open',
                                            'stop_reason' => 'Stop reason',
                                            'changed_fields' => 'Changed fields',
                                            'auto_advance' => 'Auto advance',
                                            'engaged' => 'Engaged',

                                            default => str($property)
                                                ->replace('_', ' ')
                                                ->title(),
                                        };

                                        $propertyValue = match (true) {
                                            is_bool($value) => $value ? 'Yes' : 'No',

                                            $value === null => '—',

                                            is_array($value) && $value === [] => 'None',

                                            is_array($value) => collect($value)
                                                ->map(
                                                    fn ($item) => is_string($item)
                                                        ? str($item)
                                                            ->replace('_', ' ')
                                                            ->title()
                                                        : $item
                                                )
                                                ->implode(', '),

                                            $property === 'bpm' => "{$value} BPM",

                                            str_ends_with($property, '_seconds') =>
                                                "{$value} seconds",

                                            in_array(
                                                $property,
                                                [
                                                    'mode',
                                                    'source',
                                                    'exercise_mode',
                                                    'exercise_origin',
                                                    'previous_origin',
                                                    'stop_reason',
                                                ],
                                                true
                                            ) => str((string) $value)
                                                ->replace('_', ' ')
                                                ->title(),

                                            default => (string) $value,
                                        };
                                    @endphp

                                    <div class="event-entry__property">
                                        <dt class="label">{{ $propertyLabel }} </dt>
                                        <dd class="value"> {{ $propertyValue }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        @endif
                    </li>
                @endforeach
            </ol>
        </section>
    @empty
        <p class="session-entry__empty-message">
            No product events were matched to this visit.
        </p>
    @endforelse
</div>