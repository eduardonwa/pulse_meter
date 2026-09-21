<dialog class="dialog-shell | randomizer-sessions-dialog"
    data-variant="randomizer-sessions"
    x-ref="randomizerSessionsDialog"
    x-trap.noscroll="isRandomizerSessionsDialogOpen"
    @close="isRandomizerSessionsDialogOpen = false"
    @cancel.prevent="closeRandomizerSessionsDialog()"
    @click.self="closeRandomizerSessionsDialog()"
>
    <div class="dialog-shell__content">
        <header class="dialog-shell__heading pattern-dialog__heading">
            <button
                type="button"
                class="button"
                data-type="icon"
                aria-label="Close saved sessions"
                @click="closeRandomizerSessionsDialog()"
            >
                <x-heroicon-o-x-circle />
            </button>

            <h2 class="heading">Saved random sessions</h2>
        </header>

        <div class="pattern-dialog__list">
            <p x-show="!randomizerSavedSessions.length" x-cloak>
                No saved sessions yet.
            </p>

            <template x-for="savedSession in randomizerSavedSessions" :key="savedSession.id">
                <div class="pattern-dialog__user-presets">
                    <template x-if="randomizerPendingRenameId !== savedSession.id">
                        <div class="normal-state">
                            <button
                                class="button"
                                type="button"
                                @click="loadRandomizedSession(savedSession.id)"
                                :class="{
                                    'is-selected':
                                        randomizerDraft.origin === 'saved'
                                        && randomizerDraft.sourceId === savedSession.id
                                }"
                            >
                                <strong
                                    x-text="savedSession.name"
                                ></strong>

                                <small
                                    x-text="
                                        `${savedSession.bpm} BPM · ${savedSession.timeSignature.numerator}/${savedSession.timeSignature.denominator}`
                                    "
                                ></small>
                            </button>

                            <div class="actions">
                                <button
                                    class="button edit"
                                    data-type="icon"
                                    type="button"
                                    aria-label="Rename session"
                                    @click.stop="startRandomizerRename(savedSession)"
                                >
                                    <x-heroicon-o-pencil-square />
                                </button>

                                <button
                                    class="button delete"
                                    data-type="icon"
                                    type="button"
                                    aria-label="Delete session"
                                    @click.stop="confirmDestroyRandomizedSession(savedSession)"
                                >
                                    <x-heroicon-o-trash />
                                </button>
                            </div>
                        </div>
                    </template>

                    <template x-if="randomizerPendingRenameId === savedSession.id">
                        <form
                            class="pattern-dialog__rename"
                            @submit.prevent="renameRandomizedSession()"
                            @keydown.escape.prevent.stop="cancelRandomizerRename()"
                        >
                            <label
                                class="sr-only"
                                :for="`random-session-name-${savedSession.id}`"
                            >
                                Session name
                            </label>

                            <input
                                :id="`random-session-name-${savedSession.id}`"
                                type="text"
                                maxlength="255"
                                x-model="randomizerRenameName"
                                x-init="$nextTick(() => {
                                    $el.focus()
                                    $el.select()
                                })"
                            >

                            <div class="actions">
                                <button
                                    class="button"
                                    data-type="outline"
                                    type="button"
                                    @click="cancelRandomizerRename()"
                                >
                                    Cancel
                                </button>

                                <button
                                    class="button"
                                    data-type="primary"
                                    type="submit"
                                    :disabled="!randomizerRenameName.trim()"
                                >
                                    Save
                                </button>
                            </div>
                        </form>
                    </template>
                </div>
            </template>
        </div>
    </div>
</dialog>
