export function collections() {
    return {
        // Available system collections
        getPulsePresetCollections() {
            return [
                ...new Set(
                    this.pulsePresets
                        .map(preset => preset.collection)
                        .filter(Boolean)
                ),
            ]
        },

        // Presets from one system collection
        getPulsePresetsByCollection(collection) {
            return this.pulsePresets.filter(
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