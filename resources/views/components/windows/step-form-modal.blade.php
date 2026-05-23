<dialog class="dialog-shell"
    x-ref="stepDialog"
    x-trap.noscroll="isStepFormOpen"
    @close="isStepFormOpen = false"
>
    <form class="new-exercise-form" @submit.prevent="saveStepForm()">
        <header class="heading">
            <button
                type="button"
                class="button"
                data-type="outline"
                @click="closeStepFormModal()"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
            </button>
            
            <h3 x-text="stepFormMode === 'edit' ? 'Edit exercise' : 'New exercise'"></h3>
        </header>

        <label class="exercise-name-wrapper">
            <p class="exercise-form-label">Name</p>
            <input type="text" class="name" x-model="stepForm.name">
        </label>

        <div class="form-group fields">
            <label class="exercise-bpm-wrapper">
                <p class="exercise-form-label">BPM</p>

                <x-inputs.number-picker
                    class="exercise-row__bpm"
                    options="bpmOptions"
                    model="stepForm.bpm"
                    format="(value) => value"
                />
            </label>
    
            <label class="exercise-type-wrapper">
                <p class="exercise-form-label">Type</p>
                <select x-model="stepForm.mode">
                    <option value="timer">Timer</option>
                    <option value="manual">Manual</option>
                </select>
            </label>
        </div>

        <div class="form-group" x-show="stepForm.mode === 'timer'">
            <label class="exercise-length-wrapper">
                <p class="exercise-form-label">Length</p>

                <article class="length-inputs">
                    <div class="minutes">
                        <x-inputs.number-picker
                            options="minutesOptions"
                            model="stepFormMinutes"
                        />
                        <span class="unit">m</span>
                    </div>
                    
                    <span class="colon">:</span>
                    
                    <div class="seconds">
                        <x-inputs.number-picker
                            options="secondsOptions"
                            model="stepFormSeconds"
                            format="(value) => String(value).padStart(2, '0')"
                        />
                        <span class="unit">s</span>
                    </div>
                </article>
            </label>
        </div>

        <div class="form-actions">
            <button
                type="button"
                class="button"
                data-type="outline"
                @click="closeStepFormModal()"
            >
                Cancel
            </button>

            <button
                type="submit"
                class="button"
                data-type="primary"
                x-text="stepFormMode === 'edit' ? 'Save changes' : 'Save'"
            ></button>
        </div>
    </form>
</dialog>