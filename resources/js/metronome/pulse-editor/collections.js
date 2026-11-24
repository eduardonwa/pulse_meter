export function collections() {
    return {
        // Available system collections
        getPulsePresetCollections() {
            return [
                ...new Set(
                    this.getSelectablePulsePresets()
                        .map(preset => preset.collection)
                        .filter(Boolean)
                ),
            ]
        },

        getSelectablePulsePresets() {
            return this.pulsePresets.filter(preset => !(
                preset.name === 'Common Time'
                && preset.numerator === 4
                && preset.denominator === 4
            ))
        },

        // Presets from one system collection
        getPulsePresetsByCollection(collection) {
            return this.getSelectablePulsePresets().filter(
                preset => preset.collection === collection
            )
        },

        // Human-readable collection label
        getPulseCollectionLabel(collection) {
            return collection
                .replace(/[-_]/g, ' ')
                .replace(/\b\w/g, letter => letter.toUpperCase())
        },

        selectPatternTab(tab) {
            this.activePatternTab = tab
        },
    }
}