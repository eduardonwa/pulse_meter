<dialog class="dialog-shell dialog-shell--fit-content"
    x-ref="stepDialog"
    x-trap.noscroll="isStepFormOpen"
    @close="isStepFormOpen = false"
    @click.self="closeStepFormModal()"
    @cancel.prevent="closeStepFormModal()"
>
    <form class="new-exercise-form" @submit.prevent="saveStepForm()">
        <header class="heading">
            <h3 x-text="stepFormMode === 'edit' ? 'Edit exercise' : 'New exercise'"></h3>
            
            <button
                type="button"
                class="button"
                data-type="outline"
                @click="closeStepFormModal()"
                @cancel.prevent="closeStepFormModal()"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
            </button>            
        </header>

        <label class="exercise-name-wrapper">
            <span class="exercise-form-label">Name</span>
            <input type="text" class="name" x-model="stepForm.name">
        </label>

        <div class="exercise-settings" aria-label="Exercise settings">
            <div class="exercise-type-wrapper exercise-setting-row">
                <span class="exercise-form-label" id="exercise-type-label">Type</span>
                <div class="exercise-type-toggle" role="group" aria-labelledby="exercise-type-label">
                    <button type="button" class="exercise-type-toggle__option"
                        :class="{ 'is-active': stepForm.mode === 'classic' }"
                        :aria-pressed="stepForm.mode === 'classic'"
                        @click="stepForm.mode = 'classic'">
                        Classic
                    </button>
                    <button type="button" class="exercise-type-toggle__option"
                        :class="{ 'is-active': stepForm.mode === 'drill' }"
                        :aria-pressed="stepForm.mode === 'drill'"
                        @click="stepForm.mode = 'drill'">
                        Drill
                    </button>
                </div>
            </div>

            <div class="exercise-bpm-wrapper exercise-setting-row">
                <span class="exercise-form-label">BPM</span>

                <x-inputs.number-picker
                    class="exercise-row__bpm"
                    options="bpmOptions"
                    model="stepForm.bpm"
                    format="(value) => value"
                    :controls="true"
                    decrease-label="Decrease BPM"
                    increase-label="Increase BPM"
                    hint="Scroll to change BPM"
                />
            </div>

            <div class="exercise-length-wrapper exercise-setting-row"
                x-show="stepForm.mode === 'drill'" x-cloak>
                <span class="exercise-form-label">Length</span>

                <div class="length-inputs">
                    <div class="minutes">
                        <x-inputs.number-picker
                            options="stepFormMinutesOptions"
                            model="stepFormMinutes"
                            after-change="
                                normalizeStepFormDuration();
                                $dispatch('picker:sync');
                            "
                        />
                        <span class="unit">m</span>
                    </div>

                    <span class="colon">:</span>

                    <div class="seconds">
                        <x-inputs.number-picker
                            options="stepFormSecondsOptions"
                            model="stepFormSeconds"
                            format="(value) => String(value).padStart(2, '0')"
                            after-change="
                                normalizeStepFormDuration();
                                $dispatch('picker:sync');
                            "
                        />
                        <span class="unit">s</span>
                    </div>
                </div>
            </div>

            <label class="exercise-time-signature-wrapper exercise-setting-row"
                x-cloak x-show="stepFormMode === 'create' || !hasAlphaTabPattern(steps[stepFormIndex])">
                <span class="exercise-form-label">Time signature</span>

                <div style="display: flex; gap: .5rem; align-items: center;">
                    <select x-model.number="stepForm.time_signature_numerator" aria-label="Beats per measure">
                        @foreach (range(1, 16) as $numerator)
                            <option value="{{ $numerator }}">{{ $numerator }}</option>
                        @endforeach
                    </select>
                    <span>/</span>
                    <select x-model.number="stepForm.time_signature_denominator" aria-label="Beat note value">
                        <option value="2">2</option><option value="4">4</option><option value="8">8</option><option value="16">16</option>
                    </select>
                </div>
            </label>
        </div>

        <div class="form-actions">
            @can('use-pro')
            <button x-show="stepFormMode === 'create' && canCreateTablatures" type="button" class="button" data-type="outline"
                @click="openNewTabEditor()">
                Create with tablature
            </button>
            @endcan
            <button class="button" data-type="primary" type="submit"
                x-text="stepFormMode === 'edit' ? 'Save changes' : 'Save'"
            ></button>

            <button class="button" data-type="outline" type="button"
                @click="closeStepFormModal()"
                @cancel.prevent="closeStepFormModal()"
            >
                Cancel
            </button>
        </div>
    </form>
</dialog>

{{-- Separate native dialog: the exercise does not exist until Save is confirmed. --}}

@can('use-pro')
    @include('components.windows.tab-editor')
@endcan
