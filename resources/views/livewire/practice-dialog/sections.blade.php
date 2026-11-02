<nav class="practice-dialog__sections" aria-label="Practice sections">    
    <button @class(['button', 'is-active' => $section === 'routines']) data-type="outline" type="button"
        aria-pressed="{{ $section === 'routines' ? 'true' : 'false' }}"
        wire:click="showRoutines"
        wire:loading.attr="disabled"
        wire:target="showRoutines"
    >
        <x-heroicon-o-queue-list />
        Routines
    </button>

    <button @class(['button', 'is-active' => $section === 'playlists']) data-type="outline" type="button"
        aria-pressed="{{ $section === 'playlists' ? 'true' : 'false' }}"
        wire:click="showPlaylists"
        wire:loading.attr="disabled"
        wire:target="showPlaylists"
    >
        <x-heroicon-o-numbered-list />
        Playlists
    </button>
</nav>