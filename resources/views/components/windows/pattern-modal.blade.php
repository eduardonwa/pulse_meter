<dialog
    class="dialog-shell pattern-dialog"
    x-ref="patternDialog"
    x-trap.noscroll="isPatternDialogOpen"
    @close="isPatternDialogOpen = false"
>
    <div class="pattern-dialog__content">
        <header class="heading">
            <button
                type="button"
                class="button"
                data-type="outline"
                @click="closePatternDialog()"
                @cancel.prevent="closePatternDialog()"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="1.5"
                    stroke="currentColor"
                    class="size-6"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                    />
                </svg>
            </button>

            <h3>Choose pattern</h3>
        </header>

        <div class="pattern-dialog__list">
            <button class="pattern-dialog__item" type="button" :class="{ 'is-selected': pulseDraft.origin === 'new' }" @click="selectPulseSourceFromDialog('new')">
                <strong>New Pattern</strong>
                <small>Start from a new 4/4 pattern</small>
            </button>

            <div class="pattern-dialog__group">
                <h4>Presets</h4>

                <template x-for="signature in timeSignatures" :key="signature.id">
                    <button
                        type="button"
                        class="pattern-dialog__item"
                        @click="
                            selectPulseSourceFromDialog(
                                `preset:${signature.id}`
                            )
                        "
                        :class="{
                            'is-selected':
                                pulseDraft.origin === 'preset'
                                && pulseDraft.sourceId === signature.id
                        }"
                    >
                        <strong
                            x-text="
                                `${signature.numerator}/${signature.denominator}`
                            "
                        ></strong>

                        <small
                            x-text="signature.grouping.join(' + ')"
                        ></small>
                    </button>
                </template>
            </div>

            <div class="pattern-dialog__group" x-show="userPatterns.length">
                <h4>My Patterns</h4>

                <template x-for="pattern in userPatterns" :key="pattern.id">
                    <button
                        type="button"
                        class="pattern-dialog__item"
                        @click="
                            selectPulseSourceFromDialog(
                                `user:${pattern.id}`
                            )
                        "
                        :class="{
                            'is-selected':
                                pulseDraft.origin === 'user'
                                && pulseDraft.sourceId === pattern.id
                        }"
                    >
                        <strong
                            x-text="
                                `${pattern.timeSignature.numerator}/${pattern.timeSignature.denominator}`
                            "
                        ></strong>

                        <small
                            x-text="pattern.grouping.join(' + ')"
                        ></small>
                    </button>
                </template>
            </div>
        </div>
    </div>
</dialog>