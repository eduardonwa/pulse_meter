@props([
    'title' => 'Controls',
    'open' => false,
])

<div {{ $attributes->class('routine-player__controls') }}
    x-data="{ controlsExposed: @js($open) }"
>
    <header class="header">
        <span class="header__label">
            {{ $title }}
        </span>

        <button class="button" data-type="icon" type="button"
            aria-label="Toggle {{ strtolower($title) }}"
            :aria-expanded="controlsExposed.toString()"
            @click="controlsExposed = !controlsExposed"
        >
            <x-heroicon-o-chevron-down
                x-bind:class="{
                    'rotate-180': controlsExposed
                }"
            />
        </button>
    </header>

    <div class="routine-player__controls-body"
        x-show="controlsExposed"
        x-cloak
    >
        {{ $slot }}
    </div>
</div>