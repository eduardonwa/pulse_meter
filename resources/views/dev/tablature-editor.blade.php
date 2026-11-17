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
        <section class="tab-editor__workspace" aria-label="Tablature workspace" x-data="tablatureRedesign" x-init="$nextTick(() => updateTabEditorWidth())"
            @keydown="handleTabEditorKey($event)"
            @resize.window="updateTabEditorWidth()"
            @pointerup.window="endTabDrag()"
            @pointercancel.window="endTabDrag()"
        >
            <aside class="tab-editor__rhythm" aria-label="Rhythm tools">
                <div class="tab-editor__palette-durations" role="group" aria-label="Note duration">
                    <template x-for="item in [
                        { duration: 1, name: 'Whole', symbol: '𝅝' },
                        { duration: 2, name: 'Half', symbol: '𝅗𝅥' },
                        { duration: 4, name: 'Quarter', symbol: '♩' },
                        { duration: 8, name: 'Eighth', symbol: '♫' },
                        { duration: 16, name: 'Sixteenth', symbol: '♬' },
                        ]" :key="item.duration"
                    >
                        <button type="button" class="tab-editor__palette-tool" :class="{ 'is-active': tabDraft.duration === item.duration }" :aria-pressed="(tabDraft.duration === item.duration).toString()" :title="item.name" @click="changeTabEditorDuration(item.duration)">
                            <span aria-hidden="true" x-text="item.symbol"></span>
                            <span x-text="item.name"></span>
                        </button>
                    </template>
                </div>

                <div class="tab-editor__palette-effects" role="group" aria-label="Rhythm modifiers">
                    <button type="button" class="tab-editor__palette-tool" :class="{ 'is-active': tabDraft.dotted }" :aria-pressed="Boolean(tabDraft.dotted).toString()" @click="setTabEditorRhythm({ dotted: !tabDraft.dotted })" title="Dotted duration: one and a half times the selected value">
                        <span aria-hidden="true">♩.</span> Dotted
                    </button>

                    <button type="button" class="tab-editor__palette-tool" :class="{ 'is-active': tabDraft.tuplet === 3 }" :aria-pressed="(tabDraft.tuplet === 3).toString()" @click="setTabEditorRhythm({ tuplet: tabDraft.tuplet === 3 ? null : 3 })" title="Triplet: three notes in the time of two">
                        <span aria-hidden="true">3:2</span> Triplet
                    </button>

                    <button type="button" class="tab-editor__palette-tool" @click="addTabEditorRest()" title="Insert or replace with a rest">
                        <span aria-hidden="true">𝄽</span> Rest
                    </button>
                </div>
            </aside>

            <div class="tab-editor__score" x-ref="tabEditorScore" @pointerup.window="endTabDrag()" @pointercancel.window="endTabDrag()" @resize.window="updateTabEditorWidth()" tabindex="0" role="group" aria-label="Interactive six-string tablature">
                <template x-for="(system, systemIndex) in tabEditorSystems()" :key="systemIndex">
                    <div class="tab-editor__system">
                        <div class="tab-editor__string-labels">
                            <template x-for="(name, stringIndex) in tabStringNames" :key="stringIndex">
                                <span x-text="name"></span>
                            </template>
                        </div>

                        <div class="tab-editor__grid">
                            <template x-for="(name, stringIndex) in tabStringNames" :key="stringIndex">
                                <div class="tab-editor__string-row">
                                    <template x-for="column in system" :key="`${column.index}-${column.insertion}`">
                                        <button type="button" class="tab-editor__cell"
                                            :class="{ 'is-selected': tabSelectedIndex === column.index && tabDraft.string === stringIndex + 1 && tabInsertAtBar === column.insertion, 'is-measure-end': column.measureEnd, 'is-multi-selected': !column.insertion && tabSelectedIndices.includes(column.index) && column.index < tabDraft.beats.length, 'is-insertion': column.insertion }"
                                            :aria-label="column.insertion ? `Insert note before next measure, string ${stringIndex + 1}` : `String ${stringIndex + 1}, position ${column.index + 1}`"
                                            :aria-pressed="tabSelectedIndex === column.index && tabDraft.string === stringIndex + 1 && tabInsertAtBar === column.insertion"
                                            @pointerdown="!column.insertion && startTabDrag(column.index, stringIndex + 1, $event)"
                                            @pointerenter="!column.insertion && extendTabDrag(column.index)"
                                            @click="column.insertion ? selectTabEditorInsertion(column.index, stringIndex + 1) : clickTabCell(column.index, stringIndex + 1, $event); $refs.tabEditorScoreFocus?.focus()"
                                            x-text="column.insertion ? (stringIndex + 1 === tabDraft.string ? '+' : '·') : (tabEditorNoteAt(column.index, stringIndex + 1) ?? '·')"
                                        >
                                        </button>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
            
            <span x-ref="tabEditorScoreFocus" class="sr-only" tabindex="-1">Tab keyboard controls</span>
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