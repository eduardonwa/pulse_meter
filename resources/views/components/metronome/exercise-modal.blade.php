<dialog class="new-exercise-modal" x-ref="stepDialog">
    <form class="new-exercise-form" @submit.prevent="saveStepForm()">
        <h3 x-text="stepFormMode === 'edit' ? 'Edit exercise' : 'New exercise'"></h3>

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
                @click="$refs.stepDialog.close(); resetStepForm()"
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