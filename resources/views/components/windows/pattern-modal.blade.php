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
            <button class="button" data-type="outline" type="button"
                :class="{
                    'is-selected': pulseDraft.origin === 'new'
                }"
                @click="selectPulseSourceFromDialog('new')"
            >
                <strong>New Pattern</strong>
                <small>Start from a new 4/4 pattern</small>
            </button>

            {{-- Tabs header --}}
            <x-patterns.tabs />

            {{-- System collections --}}
            <x-patterns.system-collections />

            {{-- User tab content --}}
            <x-patterns.user-collections />
        </div>
    </div>
</dialog>