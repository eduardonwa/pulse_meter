@props([
    'routine' => null,
    'routines' => null,
    'usesServerPersistence' => false,
])

<section class="metronome__panel">
    {{-- Static, accessible feedback before the practice JS chunks initialize Alpine. --}}
    <div class="practice-loading" role="status" aria-live="polite">
        <span class="practice-loading__title">Loading your exercises…</span>
        <div class="practice-loading__row" aria-hidden="true"></div>
        <div class="practice-loading__row" aria-hidden="true"></div>
        <div class="practice-loading__row" aria-hidden="true"></div>
    </div>

    <div class="practice-panel-content">
    <x-metronome.panel-tabs />

    <x-metronome.panel-sessions />

    <x-metronome.panel-exercises />

    <x-metronome.panel-settings />
    </div>
</section>