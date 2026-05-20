<div class="exercises" x-show="activeTab === 'exercises'" x-cloak>
    <article class="exercises__list">
        <header class="heading-bar">
            <h2 class="header">My exercises</h2>

            <button
                type="button"
                class="add-exercise | button"
                data-type="action-exercise"
                :disabled="steps.length >= maxSteps"
                @click="openAddStepModal()"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                Add exercise
            </button>
        </header>

        <div class="exercises-table-wrapper">
            <div class="exercises-table">
                <div class="exercises-table__head">
                    <span></span>
                    <span class="text-center">#</span>
                    <span>Name</span>

                    <div class="bpm-field | tooltip" aria-label="Scroll to change BPM">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="height: 18px;">
                            <path fill-rule="evenodd" d="M2.24 6.8a.75.75 0 0 0 1.06-.04l1.95-2.1v8.59a.75.75 0 0 0 1.5 0V4.66l1.95 2.1a.75.75 0 1 0 1.1-1.02l-3.25-3.5a.75.75 0 0 0-1.1 0L2.2 5.74a.75.75 0 0 0 .04 1.06Zm8 6.4a.75.75 0 0 0-.04 1.06l3.25 3.5a.75.75 0 0 0 1.1 0l3.25-3.5a.75.75 0 1 0-1.1-1.02l-1.95 2.1V6.75a.75.75 0 0 0-1.5 0v8.59l-1.95-2.1a.75.75 0 0 0-1.06-.04Z" clip-rule="evenodd" />
                        </svg>
                        <span>BPM</span>
                    </div>

                    <span class="text-center">Actions</span>
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
                                class="exercise-row__playback | button"
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
                                    class="pause-icon"
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
                                    class="play-icon"
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

                            <x-inputs.number-picker
                                class="exercise-row__bpm"
                                options="bpmOptions"
                                model="step.bpm"
                                format="(value) => value"
                                after-change="updateExerciseBpm(index, value)"
                            />

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