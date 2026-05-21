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
                    after-change="updateExerciseBpm(index, value)"
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
                        <input
                            type="number"
                            min="0"
                            max="5"
                            x-model.number="stepFormMinutes"
                        >
                        <span>m</span>
                    </div>
    
                    <div class="seconds">
                        <input
                            type="number"
                            min="0"
                            max="59"
                            x-model.number="stepFormSeconds"
                        >
                        <span>s</span>
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