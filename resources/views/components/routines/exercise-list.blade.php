@props([
    'template' => null,
    'dynamic' => false,
])

<section class="routine-section routine-exercises">
    <ul class="routine-exercises__list">
        <template x-for="(step, index) in selectedRoutine?.steps ?? []" :key="step.id">
            <li class="routine-exercise">
                <div class="routine-exercise__content">
                    <header class="routine-exercise__header" :data-step="String(index + 1).padStart(2, '0')">
                        <div class="routine-exercise__name">
                            <span x-text="step.name"></span>
                        </div>

                        <div class="routine-exercise__metadata">
                            <span class="routine-exercise__bpm" x-text="`${step.bpm} BPM`"></span>

                            <template x-if="step.durationSeconds">
                                <span class="routine-exercise__duration" x-text="formatDuration( step.durationSeconds )"></span>
                            </template>

                            <span class="routine-exercise__mode" x-text="step.modeLabel" ></span>
                        </div>
                    </header>

                    <template x-if="step.notes">
                        <p class="routine-exercise__notes" x-text="step.notes" ></p>
                    </template>
                </div>
            </li>
        </template>
    </ul>
</section>