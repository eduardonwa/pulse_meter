<div class="sessions-controls">
    <x-traffic-analytics.navigator
        navigation-label="Session date navigation"
        previous-action="previousSessionDay"
        next-action="nextSessionDay"
        previous-label="Previous day"
        next-label="Next day"
        :previous-disabled="! $this->canGoToPreviousSessionDay()"
        :next-disabled="! $this->canGoToNextSessionDay()"
        variant="date"
    >
        <strong class="sessions-controls__summary-primary">
            {{ $this->selectedSessionDateLabel }}
        </strong>

        <span class="sessions-controls__summary-secondary">
            {{ $this->selectedDateSessionsCount }} sessions
        </span>
    </x-traffic-analytics.navigator>

    <x-traffic-analytics.navigator
        navigation-label="Sessions pagination"

        first-action="firstSessionsPage"
        previous-action="previousSessionsPage"
        next-action="nextSessionsPage"
        last-action="lastSessionsPage"

        first-label="First"
        previous-label="Previous"
        next-label="Next"
        last-label="Last"

        :first-disabled="$this->sessionsPage <= 1"
        :previous-disabled="$this->sessionsPage <= 1"
        :next-disabled="$this->sessionsPage >= $this->getTotalSessionPages()"
        :last-disabled="$this->sessionsPage >= $this->getTotalSessionPages()"
        
        variant="pagination"
    >
        <strong class="sessions-controls__summary-primary">
            Page {{ $this->sessionsPage }}
            of {{ $this->getTotalSessionPages() }}
        </strong>

        <span class="sessions-controls__summary-secondary">
            For this day
        </span>
    </x-traffic-analytics.navigator>

    <form class="sessions-control__filters" aria-label="Session filters">
        <fieldset>
            <legend class="label">Filter by classification</legend>

            <div class="sessions-controls__filter-list">
                @foreach ($cards as $card)
                    @php
                        $key = $card['key'];
                        $isActive = $this->sessionTypeFilters[$key] ?? false;
                    @endphp
    
                    <button
                        class="sessions-control__filter"
                        type="button"
                        wire:click="toggleSessionTypeFilter('{{ $key }}')"
                        aria-pressed="{{ $isActive ? 'true' : 'false' }}"
                    >
                        {{ $card['label'] }}
                    </button>
                @endforeach
            </div>

            <button
                class="sessions-controls__reset"
                type="button"
                wire:click="resetSessionTypeFilters"
            >
                Reset filters
            </button>
        </fieldset>
    </form>
</div>