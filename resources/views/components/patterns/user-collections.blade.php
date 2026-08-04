<div class="pattern-dialog__group" x-show="activePatternTab === 'user' && userPatterns.length">
    <template x-for="savedPattern in userPatterns" :key="savedPattern.id">
        <div class="pattern-dialog__user-presets">
            {{-- ESTADO NORMAL --}}
            <template x-if="patternPendingRenameId !== savedPattern.id">
                <div class="normal-state">
                    <button class="button" type="button"
                        @click="selectPulseSourceFromDialog(`user:${savedPattern.id}`)"
                        :class="{
                            'is-selected':
                                pulseDraft.origin === 'user'
                                && pulseDraft.sourceId === savedPattern.id
                        }"
                    >
                        <strong x-text="savedPattern.name"></strong>

                        <small
                            x-text="`(${savedPattern.timeSignature.numerator}/${savedPattern.timeSignature.denominator})`"
                        ></small>
                    </button>

                    <div class="actions">
                        <button class="button edit" type="button" data-type="icon"
                            @click.stop="startRenamingPattern(savedPattern)"
                            aria-label="Rename pattern"
                        >
                            <x-heroicon-o-pencil-square />
                        </button>

                        <button class="button delete" type="button" data-type="icon"
                            @click.stop="openDeletePatternDialog(savedPattern.id)"
                            aria-label="Delete pattern"
                        >
                            <x-heroicon-o-trash />
                        </button>
                    </div>
                </div>
            </template>

            {{-- ESTADO DE RENOMBRE --}}
            <template x-if="patternPendingRenameId === savedPattern.id">
                <form class="pattern-dialog__rename"
                    @submit.prevent="submitPatternRename()"
                    @keydown.escape.prevent.stop="cancelRenamingPattern()"
                >
                    <label class="sr-only" :for="`pattern-name-${savedPattern.id}`">
                        Pattern name
                    </label>

                    <input
                        :id="`pattern-name-${savedPattern.id}`"
                        type="text"
                        maxlength="80"
                        x-model="patternRenameName"
                        x-init="$nextTick(() => {
                            $el.focus()
                            $el.select()
                        })"
                    >

                    <div class="actions">
                        <button class="button" type="button" data-type="outline"
                            @click="cancelRenamingPattern()"
                        >
                            Cancel
                        </button>

                        <button class="button" type="submit" data-type="primary"
                            :disabled="!patternRenameName.trim()"
                        >
                            Save
                        </button>
                    </div>
                </form>
            </template>
        </div>
    </template>
</div>