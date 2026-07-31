<div class="pattern-dialog__group" x-show="activePatternTab === 'user' && userPatterns.length">
    <template x-for="savedPattern in userPatterns" :key="savedPattern.id">
        <div class="pattern-dialog__user-presets">
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
                    x-text="
                        `(${savedPattern.timeSignature.numerator}/${savedPattern.timeSignature.denominator})`
                    "
                ></small>
            </button>
            
            <div class="actions">
                <button class="button edit" type="button" data-type="icon"
                    @click.stop="openRenamePatternDialog(savedPattern)"
                    aria-label="Rename pattern"
                >
                    <x-heroicon-o-pencil />
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
</div>