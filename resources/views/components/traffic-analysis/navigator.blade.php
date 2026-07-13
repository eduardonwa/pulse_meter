@props([
    'navigationLabel',
    'previousAction',
    'nextAction',
    'previousLabel' => 'Previous',
    'nextLabel' => 'Next',
    'previousDisabled' => false,
    'nextDisabled' => false,
    'variant' => null,
])

@php
    $navigatorClasses = 'sessions-controls__navigator';

    if ($variant) {
        $navigatorClasses .= " sessions-controls__navigator--{$variant}";
    }
@endphp

<nav
    {{ $attributes->class($navigatorClasses) }}
    aria-label="{{ $navigationLabel }}"
>
    <button
        class="
            sessions-controls__button
            sessions-controls__button--previous
        "
        type="button"
        wire:click="{{ $previousAction }}"
        @disabled($previousDisabled)
    >
        <x-heroicon-o-arrow-long-left
            class="sessions-controls__icon"
            aria-hidden="true"
        />

        <span>{{ $previousLabel }}</span>
    </button>

    <div class="sessions-controls__summary">
        {{ $slot }}
    </div>

    <button
        class="
            sessions-controls__button
            sessions-controls__button--next
        "
        type="button"
        wire:click="{{ $nextAction }}"
        @disabled($nextDisabled)
    >
        <x-heroicon-o-arrow-long-right
            class="sessions-controls__icon"
            aria-hidden="true"
        />

        <span>{{ $nextLabel }}</span>
    </button>
</nav>