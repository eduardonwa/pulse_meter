<dialog
    class="dialog-shell dialog-shell--fit-content"
    x-ref="pulsePatternNameDialog"
    x-trap.noscroll="isPulsePatternNameDialogOpen"
    @cancel.prevent="closePulsePatternNameDialog()"
    @close="isPulsePatternNameDialogOpen = false"
    @click.self="closePulsePatternNameDialog()"
>
    <div class="dialog-shell__content">
        <header class="dialog-shell__heading">
            <h2 class="heading" id="pulse-pattern-name-title">Name your pattern</h2>
        </header>

        <form
            class="routine-dialog__rename"
            @submit.prevent="submitPulsePatternName()"
        >
            <label for="pulse-pattern-name">Pattern name</label>
            <input
                id="pulse-pattern-name"
                x-ref="pulsePatternNameInput"
                type="text"
                x-model="pulsePatternNameInput"
                maxlength="80"
                required
                autocomplete="off"
                :disabled="isPulsePatternNameSaving"
            >

            <div class="actions">
                <button
                    type="button"
                    class="button"
                    data-variant="outline"
                    :disabled="isPulsePatternNameSaving"
                    @click="closePulsePatternNameDialog()"
                >
                    Cancel
                </button>
                <button
                    type="submit"
                    class="button"
                    data-type="primary"
                    :disabled="isPulsePatternNameSaving || !pulsePatternNameInput.trim()"
                >
                    Save
                </button>
            </div>
        </form>
    </div>
</dialog>
