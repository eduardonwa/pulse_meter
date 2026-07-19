@php
    $productEventsContentId =
        $sessionId . '-product-events-content';
@endphp

<x-collapse-toggle
    label-class="collapse-toggle__label"
    label="Product events"
    :controls="$productEventsContentId"
/>

<div class="product-events__content"
    id="{{ $productEventsContentId }}"
    x-show="open"
    x-cloak
>   
    {{-- PRODUCT EVENTS --}}
    @if ($events->isEmpty())
        <p class="session-entry__empty-message"> No product events were matched to this visit. </p>
    @else
        {{-- PRODUCT EVENT TABS --}}
        <div class="product-session__events-header">
            @include('filament.pages.traffic-analytics.session.event-results.header', [
                'events' => $events,
                'eventTypeCounts' => $eventTypeCounts,
            ])
        </div>
        
        <ol class="product-session__events">
            @include('filament.pages.traffic-analytics.session.event-results.results', [
                'events' => $events,
            ])
        </ol>
    @endif
</div>