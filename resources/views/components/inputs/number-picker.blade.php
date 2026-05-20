@props([
    'options' => '[]',
    'model',
    'format' => '(value) => value',
    'disabled' => 'false',
    'afterChange' => null,
])

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
    x-effect="syncExternalValue()"
    @scroll.debounce.150ms="syncFromScroll"
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