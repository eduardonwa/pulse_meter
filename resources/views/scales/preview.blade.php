<x-layouts.dorelog>
    <main class="container" style="max-width: 900px; padding-block: 3rem;">
        <x-scale-reference-card
            title="B♭ Mixolydian"
            description="B♭ Mixolydian is a major mode with a lowered seventh degree. It contains the notes B♭, C, D, E♭, F, G and A♭."
            :notes="['B♭', 'C', 'D', 'E♭', 'F', 'G', 'A♭', 'B♭']"
            :degrees="['1', '2', '3', '4', '5', '6', '♭7', '8']"
            :formula="['W', 'W', 'H', 'W', 'W', 'H', 'W', '—']"
            style="--scale-note-count: 8;"
        />
    </main>
</x-layouts.dorelog>
