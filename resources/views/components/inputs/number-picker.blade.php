@props([
    'options' => '[]',
    'model',
    'format' => '(value) => value',
    'disabled' => 'false',
    'afterChange' => null,
    'controls' => false,
    'controlsOnMobile' => true,
    'decreaseLabel' => 'Decrease value',
    'increaseLabel' => 'Increase value',
    'hint' => null,
])

@if ($controls)
    <div
        {{ $attributes->class([
            'number-picker',
            'number-picker--controls-desktop-only' => !$controlsOnMobile,
        ]) }}
        x-data="numberPicker({
            options: {{ $options }},
            getValue: () => {{ $model }},
            setValue: value => {
                {{ $model }} = value;
                {{ $afterChange ?: '' }}
            },
            disabled: () => {{ $disabled }},
            format: {{ $format }},
        })"
        x-init="init()"
        x-effect="$nextTick(() => syncExternalValue())"
        @picker:sync.window="$nextTick(() => syncExternalValue())"
    >
        <button
            type="button"
            aria-label="{{ $decreaseLabel }}"
            title="{{ $decreaseLabel }}"
            :disabled="!canGoPrevious()"
            @click.stop="previous()"
            @class([
                'number-picker__control',
                'number-picker__control--desktop-only' => !$controlsOnMobile,
            ])
        >
            &minus;
        </button>

        <div
            class="picker-column"
            x-ref="scroller"
            @scroll.debounce.150ms="syncFromScroll()"
            @if ($hint)
                title="{{ $hint }}"
                aria-label="{{ $hint }}"
            @endif
        >
            <template x-for="option in options" :key="option">
                <div
                    class="picker-option"
                    :data-value="option"
                    x-text="format(option)"
                    @click.stop="select(option)"
                    :class="{ 'is-selected': isSelected(option) }"
                ></div>
            </template>
        </div>

        <button
            type="button"
            aria-label="{{ $increaseLabel }}"
            title="{{ $increaseLabel }}"
            :disabled="!canGoNext()"
            @click.stop="next()"
            @class([
                'number-picker__control',
                'number-picker__control--desktop-only' => !$controlsOnMobile,
            ])
        >
            &plus;
        </button>
    </div>
@else
    <div
        {{ $attributes->class(['picker-column']) }}
        x-data="numberPicker({
            options: {{ $options }},
            getValue: () => {{ $model }},
            setValue: value => {
                {{ $model }} = value;
                {{ $afterChange ?: '' }}
            },
            disabled: () => {{ $disabled }},
            format: {{ $format }},
        })"
        x-effect="
            {{ $model }};
             $nextTick(() => syncExternalValue())
        "
        x-ref="scroller"
        x-init="init()"
        @picker:sync.window="$nextTick(() => syncExternalValue())"
        @scroll.debounce.150ms="syncFromScroll()"
    >
        <template x-for="option in options" :key="option">
            <div
                class="picker-option"
                :data-value="option"
                x-text="format(option)"
                @click.stop="select(option)"
                :class="{ 'is-selected': isSelected(option) }"
            ></div>
        </template>
    </div>
@endif