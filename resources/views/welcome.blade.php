<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>pulse</title>
    @vite(['resources/styles/main.scss', 'resources/js/app.js'])
    <style> [x-cloak] { display: none !important; } </style>
</head>
<body>

    <header class="main-heading">
        <h2 class="heading-2">Pulso</h2>
        <p>A metronome for the practicing musician</p>
    </header>

    <main class="metronome"
        x-data="routinePlayer([
            {
                name: 'Alternate Picking',
                bpm: 100,
                mode: 'timer',
                duration_seconds: 5
            },
            {
                name: 'Legato',
                bpm: 80,
                mode: 'timer',
                duration_seconds: 5
            },
            {
                name: 'Sweep Picking',
                bpm: 90,
                mode: 'timer',
                duration_seconds: null
            }
        ])"
    >
        <section class="metronome__main">
            <h4 class="current-beat" x-text="currentBeat"></h4>
            
            <article class="beats">
                <template x-for="beat in beatsPerMeasure" :key="beat">
                    <div
                        class="beat-mark"
                        x-text="beat"
                        :class="{ 'is-active': currentBeat === beat }"
                    ></div>
                </template>
            </article>

            <div class="tempo-control">
                <div class="tempo-display">
                    <label for="bpm">
                        <input
                            class="tempo-input"
                            type="number"
                            min="30"
                            max="400"
                            x-model.number="metronome.bpm"
                            @change="isPlaying && restartMetronomeSession()"
                        >
                        <span class="tempo-unit | uppercase">bpm</span>
                    </label>
                </div>

                <input
                    class="tempo-range"
                    type="range"
                    min="30"
                    max="400"
                    value="100"
                    x-model.number="metronome.bpm"
                    @change="isPlaying && restartMetronomeSession()"
                >
            </div>

            <div class="play">
                <button class="button uppercase" data-type="play-metronome" @click="toggle()">
                    <span x-text="isPlaying ? 'Stop' : 'Start'"></span>
                </button>
            </div>
        </section>

        <section class="metronome__panel">
            <article class="tabs">
                <button
                    class="button | uppercase"
                    data-type="tab-btn"
                    type="button"
                    :class="{ 'is-active': activeTab === 'types' }"
                    @click="activeTab = 'types'"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.153 16.556 18 12 18s-8.25-1.847-8.25-4.125v-3.75m16.5 0c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" />
                    </svg>
                    Types
                </button>
    
                <button
                    class="button | uppercase"
                    data-type="tab-btn"
                    type="button"
                    :class="{ 'is-active': activeTab === 'exercises' }"
                    @click="activeTab = 'exercises'"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    Exercises
                </button>
    
                <button
                    class="button | uppercase"
                    data-type="tab-btn"
                    type="button"
                    :class="{ 'is-active': activeTab === 'settings' }"
                    @click="activeTab = 'settings'"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" />
                    </svg>
                    Settings
                </button>
            </article>

            <div class="modes" x-show="activeTab === 'types'" x-cloak>
                <div class="header">
                    <h2>session type</h2>
                    
                    <label for="mode">
                        <select class="mode-selector" x-model="metronome.mode">
                            <option value="timer">Timer</option>
                            <option value="manual">Manual</option>
                        </select>
                    </label>
                </div>
    
                <article class="mode-timer" x-show="metronome.mode === 'timer'">
                    <div class="time-display">
                        <div class="picker-column minutes" x-ref="minutesPicker" @scroll.debounce.150ms="syncPickerFromScroll('minutes')">
                            <template x-for="minute in minutesOptions" :key="minute">
                                <div
                                    class="picker-option"
                                    :data-value="minute"
                                    x-text="minute"
                                    @click="!isPlaying && (metronomeMinutes = minute)"
                                    :class="{ 'is-selected': metronomeMinutes === minute }"
                                ></div>
                            </template>
                        </div>

                        <span class="colon">:</span>

                        <div class="picker-column seconds" x-ref="secondsPicker" @scroll.debounce.150ms="syncPickerFromScroll('seconds')">
                            <template x-for="second in secondsOptions" :key="second">
                                <div
                                    class="picker-option"
                                    :data-value="second"
                                    x-text="String(second).padStart(2, '0')"
                                    @click="!isPlaying && (metronomeSeconds = second)"
                                    :class="{ 'is-selected': metronomeSeconds === second }"
                                ></div>
                            </template>
                        </div>
                    </div>
                </article>
            </div>

            <div class="exercises" x-show="activeTab === 'exercises'" x-cloak>
                <article class="exercises__list">
                    <header class="heading-bar">
                        <h2>My exercises</h2>

                        <button
                            type="button"
                            class="button"
                            style="border: 1px solid var(--neutral-600);"
                            data-type="action-exercise"
                            :disabled="steps.length >= maxSteps"
                            @click="openAddStepModal()"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                        </button>
                    </header>

                    <div class="exercises-table-wrapper">
                        <div class="exercises-table">
                            <div class="exercises-table__head">
                                <span></span>
                                <span>#</span>
                                <span>Name</span>
                                <span>BPM</span>
                                <span>Actions</span>
                            </div>

                            <ul class="exercises-table__body">
                                <template x-for="(step, index) in steps" :key="index">
                                    <li
                                        class="exercise-row"
                                        :class="{ 'is-active': currentIndex === index }"
                                        @click="currentIndex = index"
                                    >
                                        <button
                                            type="button"
                                            class="button"
                                            data-type="action-exercise"
                                            @click.stop="activeExerciseIndex === index && isPlaying ? stop() : startExercise(index)"
                                        >
                                            <!-- Pause icon -->
                                            <svg
                                                x-show="activeExerciseIndex === index && isPlaying"
                                                xmlns="http://www.w3.org/2000/svg"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke-width="1.5"
                                                stroke="currentColor"
                                                class="size-6"
                                            >
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.25 9v6m-4.5 0V9M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                            </svg>

                                            <!-- Play icon -->
                                            <svg
                                                x-show="!(activeExerciseIndex === index && isPlaying)"
                                                xmlns="http://www.w3.org/2000/svg"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke-width="1.5"
                                                stroke="currentColor"
                                                class="size-6"
                                            >
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.91 11.672a.375.375 0 0 1 0 .656l-5.603 3.113a.375.375 0 0 1-.557-.328V8.887c0-.286.307-.466.557-.327l5.603 3.112Z" />
                                            </svg>
                                        </button>

                                        <span
                                            class="exercise-row__number"
                                            x-text="index + 1"
                                        ></span>

                                        <input
                                            class="exercise-row__name"
                                            type="text"
                                            x-model="step.name"
                                            @click.stop
                                            @focus="currentIndex = index"
                                        >

                                        <input
                                            class="exercise-row__bpm"
                                            type="number"
                                            min="30"
                                            max="300"
                                            x-model.number="step.bpm"
                                            @click.stop
                                            @focus="currentIndex = index"
                                            @change="currentIndex = index; updateBpm()"
                                        >

                                        <div class="exercise-row__actions">
                                            <button
                                                type="button"
                                                class="button"
                                                data-type="action-exercise"
                                                @click.stop="openEditStepModal(index)"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="clr-primary-400">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                                                </svg>
                                            </button>

                                            <button
                                                type="button"
                                                class="button"
                                                data-type="action-exercise"
                                                @click.stop="currentIndex = index; removeCurrentStep()"
                                                :disabled="steps.length <= 1"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="error">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                </svg>
                                            </button>
                                        </div>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </div>

                    <dialog x-ref="addStepDialog">
                        <form @submit.prevent="saveNewStep()">
                            <h3>New exercise</h3>

                            <label>
                                Name
                                <input type="text" x-model="newStep.name">
                            </label>

                            <label>
                                BPM
                                <input type="number" min="30" max="300" x-model.number="newStep.bpm">
                            </label>

                            <label>
                                Type
                                <select x-model="newStep.mode">
                                    <option value="timer">Timer</option>
                                    <option value="manual">Manual</option>
                                </select>
                            </label>

                            <div x-show="newStep.mode === 'timer'">
                                <label>
                                    Length
                                    <input type="number" min="0" max="5" x-model.number="newStepMinutes">
                                    <span>m</span>

                                    <input type="number" min="0" max="59" x-model.number="newStepSeconds">
                                    <span>s</span>
                                </label>
                            </div>

                            <button type="submit">Save</button>
                            <button type="button" @click="$refs.addStepDialog.close()">Cancel</button>
                        </form>
                    </dialog>

                    <p class="total-exercises">
                        <span x-text="steps.length"></span> / <span x-text="maxSteps"></span> exercises
                    </p>
                </article>
            </div>

            <div class="settings" x-show="activeTab === 'settings'" x-cloak>
                
                <label class="timer-options" for="autoadvance">
                    <p>Timer options</p>
                    <div class="option-field">
                        <input type="checkbox" x-model="autoAdvance">
                        <span>Auto-advance to next exercise</span>
                    </div>
                </label>

            </div>
        </section>
    </main>
</body>
</html>