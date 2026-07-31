<template x-for="collection in getPulsePresetCollections()" :key="collection">
    <div class="pattern-dialog__group" x-show="activePatternTab === collection">
        <template x-for="preset in getPulsePresetsByCollection(collection)" :key="preset.id">
            <button type="button" class="button | pattern-dialog__item"
                @click="selectPulseSourceFromDialog(`preset:${preset.id}`)"
                :class="{
                    'is-selected':
                        pulseDraft.origin === 'preset'
                        && pulseDraft.sourceId === preset.id
                }"
            >
                <strong
                    class="preset-name"
                    x-text="preset.name"
                ></strong>

                <small
                    class="preset-meter"
                    x-text="`(${preset.numerator}/${preset.denominator})`"
                ></small>
            </button>
        </template>
    </div>
</template>