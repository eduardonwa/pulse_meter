<dialog class="dialog-shell | local-exercises-dialog"
    x-ref="localRoutineImportDialog"
    x-effect="isLocalRoutineImportOpen
        ? (!$el.open && $el.showModal())
        : ($el.open && $el.close())
    "
    x-trap.noscroll="isLocalRoutineImportOpen"
    @cancel.prevent
>
    <header class="dialog-shell__heading local-exercises-dialog__heading">
        <strong
            x-text="
                `${pendingLocalRoutineImport?.steps?.length ?? 0}
                ${(pendingLocalRoutineImport?.steps?.length ?? 0) === 1
                    ? 'exercise'
                    : 'exercises'
                } found`
            "
        ></strong>
    </header>

    <div class="dialog-shell__content local-exercises-dialog__content">
        <p x-show="pendingLocalRoutineImport?.type === 'first_import'">
            We found exercises you created earlier in Free Mode.
            Would you like to keep them or start from scratch?
        </p>

        <p x-show="pendingLocalRoutineImport?.type === 'update'">
            Your Free exercises have changed.
            Would you like to update your imported routine
            with these changes?
        </p>

        <p>
            Other routines and playlists won't change.
        </p>

        <div x-show="localRoutineImportLimitReached" x-cloak>
            <p>
                Your Trial already has the maximum of 3 routines.
                Upgrade to Pro to keep your Free exercises.
            </p>

            <p>
                Get unlimited routines, playlists,
                and save shared routines.
            </p>

            <a
                class="button"
                data-type="primary"
                href="{{ route('billing.index') }}"
            >
                Upgrade to Pro
            </a>
        </div>

        <p
            role="alert"
            x-show="localRoutineImportError"
            x-text="localRoutineImportError"
            x-cloak
        ></p>
    </div>

    <footer class="local-exercises-dialog__actions">
        <button
            class="button"
            data-type="secondary"
            type="button"
            :disabled="isLocalRoutineImportBusy"
            @click="
                resolveLocalRoutineImport(
                    'keep_server'
                )
            "
        >
            <span x-show="!isLocalRoutineImportBusy">
                Start from scratch
            </span>

            <span x-show="isLocalRoutineImportBusy" x-cloak>
                Processing...
            </span>
        </button>

        <button
            class="button"
            data-type="primary"
            type="button"
            :disabled="isLocalRoutineImportBusy"
            @click="
                resolveLocalRoutineImport(
                    'use_free'
                ).then(result => {
                    if (result === 'imported') {
                        window.location.reload()
                    }
                })
            "
        >
            <span
                x-text="
                    isLocalRoutineImportBusy
                        ? 'Importing...'
                        : pendingLocalRoutineImport?.type === 'first_import'
                            ? 'Keep my exercises'
                            : 'Update from Free'
                "
            ></span>
        </button>
    </footer>
</dialog>