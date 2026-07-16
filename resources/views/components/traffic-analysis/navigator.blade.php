@props([
    'navigationLabel',

    // ACTIONS
    'firstAction' => null,
    'previousAction',
    'nextAction',
    'lastAction' => null,

    // LABELS
    'firstLabel' => 'First',
    'previousLabel' => 'Previous',
    'nextLabel' => 'Next',
    'lastLabel' => 'Last',

    // DISABLED
    'firstDisabled' => false,
    'previousDisabled' => false,
    'nextDisabled' => false,
    'lastDisabled' => false,

    'variant' => null,
])

@php
    $navigatorClasses = 'sessions-controls__navigator';

    if ($variant) {
        $navigatorClasses .= " sessions-controls__navigator--{$variant}";
    }
@endphp

<nav {{ $attributes->class($navigatorClasses) }} aria-label="{{ $navigationLabel }}">
    <div class="sessions-controls__button-group sessions-controls__button-group--backward">
        @if ($firstAction)
            <button class="sessions-controls__button sessions-controls__button--first"
                type="button"
                wire:click="{{ $firstAction }}"
                @disabled($firstDisabled)
            >
                <x-heroicon-o-chevron-double-left class="sessions-controls__icon" aria-hidden="true" />
                <span>{{ $firstLabel }}</span>
            </button>
        @endif
    
        <button class="sessions-controls__button sessions-controls__button--previous"
            type="button"
            wire:click="{{ $previousAction }}"
            @disabled($previousDisabled)
        >
            <x-heroicon-o-arrow-long-left class="sessions-controls__icon" aria-hidden="true"/>
            <span>{{ $previousLabel }}</span>
        </button>
    </div>

    <div class="sessions-controls__summary"> {{ $slot }} </div>

    <div class="sessions-controls__button-group sessions-controls__button-group--forward">
        <button class="sessions-controls__button sessions-controls__button--next"
            type="button"
            wire:click="{{ $nextAction }}"
            @disabled($nextDisabled)
        >
            <span>{{ $nextLabel }}</span>
            <x-heroicon-o-arrow-long-right class="sessions-controls__icon" aria-hidden="true"/>
        </button>
    
        @if ($lastAction)
            <button class="sessions-controls__button sessions-controls__button--last"
                type="button"
                wire:click="{{ $lastAction }}"
                @disabled($lastDisabled)
            >
                <span>{{ $lastLabel }}</span>
                <x-heroicon-o-chevron-double-right class="sessions-controls__icon" aria-hidden="true"/>
            </button>
        @endif
    </div>
</nav>