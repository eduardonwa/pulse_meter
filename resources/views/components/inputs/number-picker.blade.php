@props([
    'options' => '[]',
    'model',
    'format' => '(value) => value',
    'disabled' => 'false',
    'afterChange' => null,
    'controls' => false,
    'decreaseLabel' => 'Decrease value',
    'increaseLabel' => 'Increase value',
])

@if ($controls)
    <div
        {{ $attributes->class(['number-picker']) }}
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
            class="number-picker__control"
            aria-label="{{ $decreaseLabel }}"
            title="{{ $decreaseLabel }}"
            :disabled="!canGoPrevious()"
            @click.stop="previous()"
        >
            &minus;
        </button>

        <div
            class="picker-column"
            x-ref="scroller"
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

        <button
            type="button"
            class="number-picker__control"
            aria-label="{{ $increaseLabel }}"
            title="{{ $increaseLabel }}"
            :disabled="!canGoNext()"
            @click.stop="next()"
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
        x-ref="scroller"
        x-init="init()"
        x-effect="$nextTick(() => syncExternalValue())"
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