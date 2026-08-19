@props([
    'template' => null,
    'dynamic' => false,
])

<section class="routine-section routine-exercises">
    <ul class="routine-exercises__list">
        <template x-for="step in selectedRoutine?.steps ?? []" :key="step.id">
            <li class="routine-exercise">
                <div class="routine-exercise__content">
                    <header class="routine-exercise__header">
                        <h4 class="routine-exercise__name" x-text="step.name"></h4>

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