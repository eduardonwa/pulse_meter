<nav class="practice-dialog__sections" aria-label="Practice sections">    
    <button
        type="button"
        data-type="outline"
        wire:click="showRoutines"
        wire:loading.attr="disabled"
        wire:target="showRoutines"
        @class(['button', 'is-active' => $section === 'routines'])
        aria-pressed="{{ $section === 'routines' ? 'true' : 'false' }}"
    >
        <x-heroicon-o-queue-list />
        Routines
    </button>

    <button
        type="button"
        data-type="outline"
        wire:click="showPlaylists"
        wire:loading.attr="disabled"
        wire:target="showPlaylists"
        @class(['button', 'is-active' => $section === 'playlists'])
        aria-pressed="{{ $section === 'playlists' ? 'true' : 'false' }}"
    >
        <x-heroicon-o-numbered-list />
        Playlists
    </button>
</nav>