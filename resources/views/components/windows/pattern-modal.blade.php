<dialog
    class="dialog-shell | pattern dialog"
    data-variant="pattern-dialog"
    x-ref="patternDialog"
    x-trap.noscroll="isPatternDialogOpen"
    @close="isPatternDialogOpen = false"
    @click.self="closePatternDialog()"
>
    <div class="pattern-dialog__content">
        <header class="heading">
            <button
                type="button"
                class="button"
                data-type="icon"
                @click="closePatternDialog()"
                @cancel.prevent="closePatternDialog()"
            >
                <x-heroicon-o-x-circle />
            </button>

            <h2 class="pattern-heading">Choose pattern</h2>
        </header>

        <div class="pattern-dialog__list">
            <button class="button" data-type="outline" type="button" :class="{ 'is-selected': pulseDraft.origin === 'new' }" @click="selectPulseSourceFromDialog('new')">
                <strong>New Pattern</strong>
                <small>Start from a new 4/4 pattern</small>
            </button>

            <div class="pattern-dialog__group">
                <h4>Presets</h4>

                <template x-for="preset in pulsePresets" :key="preset.id">
                    <button type="button" class="button | pattern-dialog__item"
                        @click="selectPulseSourceFromDialog(`preset:${preset.id}`)"
                        :class="{
                            'is-selected':
                                pulseDraft.origin === 'preset'
                                && pulseDraft.sourceId === preset.id
                        }"
                    >
                        <strong
                            x-text="
                                `${preset.numerator}/${preset.denominator}`
                            "
                        ></strong>

                        <small
                            x-text="preset.grouping.join(' + ')"
                        ></small>
                    </button>
                </template>
            </div>

            <div class="pattern-dialog__group" x-show="userPatterns.length">
                <h4>My Patterns</h4>

                <template x-for="savedPattern in userPatterns" :key="savedPattern.id">
                    <div class="pattern-dialog__row">
                        <button
                            type="button"
                            class="pattern-dialog__item"
                            @click="
                                selectPulseSourceFromDialog(
                                    `user:${savedPattern.id}`
                                )
                            "
                            :class="{
                                'is-selected':
                                    pulseDraft.origin === 'user'
                                    && pulseDraft.sourceId === savedPattern.id
                            }"
                        >
                            <strong
                                x-text="
                                    `${savedPattern.timeSignature.numerator}/${savedPattern.timeSignature.denominator}`
                                "
                            ></strong>

                            <small
                                x-text="savedPattern.grouping.join(' + ')"
                            ></small>
                        </button>

                        <button
                            type="button"
                            class="button"
                            data-type="icon"
                            @click.stop="openDeletePatternDialog(savedPattern.id)"
                            aria-label="Delete pattern"
                        >
                            <x-heroicon-o-trash />
                        </button>
                    </div>
                </template>
            </div>
        </div>
    </div>
</dialog>