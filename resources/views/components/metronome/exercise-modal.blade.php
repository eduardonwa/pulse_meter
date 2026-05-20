<dialog x-ref="stepDialog">
    <form @submit.prevent="saveStepForm()">
        <h3 x-text="stepFormMode === 'edit' ? 'Edit exercise' : 'New exercise'"></h3>

        <label>
            Name
            <input type="text" x-model="stepForm.name">
        </label>

        <label>
            BPM
            <input
                type="number"
                min="30"
                max="300"
                x-model.number="stepForm.bpm"
            >
        </label>

        <label>
            Type
            <select x-model="stepForm.mode">
                <option value="timer">Timer</option>
                <option value="manual">Manual</option>
            </select>
        </label>

        <div x-show="stepForm.mode === 'timer'">
            <label>
                Length

                <input
                    type="number"
                    min="0"
                    max="5"
                    x-model.number="stepFormMinutes"
                >
                <span>m</span>

                <input
                    type="number"
                    min="0"
                    max="59"
                    x-model.number="stepFormSeconds"
                >
                <span>s</span>
            </label>
        </div>

        <button
            type="submit"
            x-text="stepFormMode === 'edit' ? 'Save changes' : 'Save'"
        ></button>

        <button
            type="button"
            @click="$refs.stepDialog.close(); resetStepForm()"
        >
            Cancel
        </button>
    </form>
</dialog>