<nav class="creative-mode-selector" aria-label="Creative session type">
    <button
        class="button"
        data-type="outline"
        type="button"
        @click="selectCreativeMode('pulse-editor')"
        :class="{ 'is-selected': creativeMode === 'pulse-editor' }"
        :aria-pressed="creativeMode === 'pulse-editor'"
    >
        Pulse Editor
    </button>

    <button
        class="button"
        data-type="outline"
        type="button"
        @click="selectCreativeMode('randomizer')"
        :class="{ 'is-selected': creativeMode === 'randomizer' }"
        :aria-pressed="creativeMode === 'randomizer'"
    >
        Randomizer
    </button>
</nav>
