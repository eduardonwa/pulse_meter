<dialog
    class="dialog-shell"
    x-ref="renamePatternDialog"
>
    <form class="modal-panel" data-type="update" @submit.prevent="confirmRenamePattern()" @click.stop>
        <div class="group">
            <h2 class="group__header">
                Rename pattern
            </h2>

            <p class="group__hint">
                Choose a new name for this pattern.
            </p>
        </div>

        <div class="field">
            <label for="pattern-name">
                Pattern name
            </label>

            <input
                id="pattern-name"
                type="text"
                x-model="patternRenameName"
                maxlength="255"
                required
            >
        </div>

        <div class="modal-panel__actions">
            <button class="button" type="button" data-type="outline" @click="closeRenamePatternDialog()">
                Cancel
            </button>

            <button class="button" type="submit" data-type="outline">
                Rename
            </button>
        </div>
    </form>
</dialog>