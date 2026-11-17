<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Tablature Editor Prototype — Dorelog</title>

    @vite(['resources/styles/main.scss', 'resources/js/tablature-editor-prototype.js'])
</head>
<body>
    <main class="tab-editor">
        {{-- Exercise name and save actions --}}
        <header class="tab-editor__header">
            <h2 class="exercise-name">Nombre del ejercicio</h2>

            <div class="tab-editor__actions">
                <button class="button save" data-type="icon-text" type="button">
                    <x-lucide-save /> Save
                </button>
                <button class="button save-close" data-type="icon-text" type="button">
                    <x-lucide-save-check /> Save and close
                </button>
                <button class="button" data-type="outline" type="button">
                    Cancel
                </button>
            </div>
        </header>

        {{-- import --}}
        <section class="tab-editor__import-bar" aria-label="Import options">
            <button class="button" data-type="secondary" data-variant="outline" type="button">Import MusicXML</button>
        </section>

        {{-- shortcuts --}}
        <section class="tab-editor__shortcuts-bar" aria-label="Editor shortcuts">
            <button class="badge badge--neutral" type="button">Shortcuts</button>
        </section>

        {{-- main editing area --}}
        <section class="tab-editor__workspace" aria-label="Tablature workspace">
            <aside class="tab-editor__rhythm" aria-label="Rhythm tools">
                {{-- rhythm controls --}}
            </aside>

            <div class="tab-editor__score">
                {{-- alphatab editor --}}
            </div>
        </section>

        {{-- playback controls --}}
        <footer class="tab-editor__transport"
            x-data="{
                playing: false,
                loop: false,
                countIn: false,
                metronome: false
            }"
        >
            <button class="button" data-type="icon" type="button"
                :aria-label="playing ? 'Pause' : 'Play'"
                :class="{ 'is-active': playing }"
                @click="playing = !playing"
            >
                <template x-if="!playing">
                    <x-lucide-play height="50" width="50" />
                </template>

                <template x-if="playing">
                    <x-lucide-pause height="50" width="50" />
                </template>
            </button>

            <div class="tab-editor__playback-options">
                <button class="button" data-type="icon-text" type="button" aria-label="Loop"
                    x-on:click="loop = !loop"
                    x-bind:class="{ 'is-active': loop }"
                    x-bind:aria-pressed="loop.toString()"
                >
                    <x-lucide-repeat />
                </button>

                <button class="button" data-type="icon-text" type="button" aria-label="Count-in"
                    x-on:click="countIn = !countIn"
                    x-bind:class="{ 'is-active': countIn }"
                    x-bind:aria-pressed="countIn.toString()"
                >
                    <x-lucide-timer />
                </button>

                <button class="button" data-type="icon-text" type="button" aria-label="Metronome"
                    x-on:click="metronome = !metronome"
                    x-bind:class="{ 'is-active': metronome }"
                    x-bind:aria-pressed="metronome.toString()"
                >
                    <x-lucide-metronome />
                </button>
            </div>
        </footer>
    </main>
</body>
</html>