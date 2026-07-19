@props([
    'label',
    'controls',
    'labelClass' => null,
])

<button class="collapse-toggle"
    type="button"
    x-on:click="open = ! open"
    x-bind:aria-expanded="open.toString()"
    aria-controls="{{ $controls }}"
    {{ $attributes }}
>
    <x-heroicon-o-chevron-down
        class="collapse-toggle__icon"
        x-bind:class="{ 'is-open': open }"
        aria-hidden="true"
    />

    <span class="collapse-toggle__label" @class([$labelClass])>
        {{ $label }}
    </span>
</button>