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