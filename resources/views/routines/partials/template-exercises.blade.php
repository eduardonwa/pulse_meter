<section class="routine-player__queue">
    <header class="routine-player__queue-header display-none--until-desktop">
        <h2 class="routine-player__section-title">
            {{ $routine->title }}
        </h2>
    </header>

    <ol class="routine-player__exercise-list">
        @foreach ($template->steps as $step)
            @php
                $stepName = app()->isLocale('en')
                    ? ($step->name_en ?: $step->name_es)
                    : $step->name_es;

                $stepNotes = app()->isLocale('en')
                    ? ($step->notes_en ?: $step->notes_es)
                    : $step->notes_es;

                $duration = $step->duration_seconds;

                $durationLabel = match (true) {
                    $step->mode === 'classic' => 'Classic',
                    ! $duration => null,
                    $duration < 60 => "{$duration} sec",
                    $duration % 60 === 0 => ((int) ($duration / 60)) . ' min',

                    default =>
                        intdiv($duration, 60)
                        . ' min '
                        . ($duration % 60)
                        . ' sec',
                };
            @endphp

            <li class="routine-player__exercise"
                x-data="{ detailsOpen: false }"

                @routine:exercise-clear-active.window="
                    detailsOpen = false
                "

                @routine:exercise-active.window="
                    const isActive = $event.detail.index === {{ $loop->index }};
                    detailsOpen = isActive && {{ $step->alpha_tex ? 'true' : 'false' }};
                "
            >
                <button class="routine-player__exercise-trigger" type="button"
                    :class="{
                        'routine-player__exercise-trigger--active':
                            activeExerciseIndex === {{ $loop->index }}
                    }"
                    :aria-pressed="
                        (activeExerciseIndex === {{ $loop->index }}).toString()
                    "
                    @if (in_array($viewerType, ['trial', 'pro', 'lifetime'], true))
                        @click="startExercise({{ $loop->index }})"
                    @else
                        disabled
                    @endif
                >
                    <span class="routine-player__exercise-position">
                        {{ str($loop->iteration)->padLeft(2, '0') }}
                    </span>

                    <span class="routine-player__exercise-content">
                        <strong class="routine-player__exercise-name">
                            {{ $stepName }}
                        </strong>

                        @if ($stepNotes)
                            <small class="routine-player__exercise-notes">
                                {{ $stepNotes }}
                            </small>
                        @endif
                    </span>

                    <span class="routine-player__exercise-metadata">
                        <span>{{ $step->bpm }} BPM</span>

                        @if ($durationLabel)
                            <span
                                @if ($step->mode === 'timer' && in_array($viewerType, ['trial', 'pro', 'lifetime'], true))
                                    x-text=" isPlaying && activeExerciseIndex === {{ $loop->index }} && remaining !== null
                                        ? (remaining < 60 ? `${remaining} sec` : remaining % 60 === 0
                                            ? `${remaining / 60} min`
                                            : `${Math.floor(remaining / 60)} min ${remaining % 60} sec`
                                        )
                                        : @js($durationLabel)
                                    "
                                @endif
                            >
                                {{ $durationLabel }}
                            </span>
                        @endif
                    </span>

                    @if (in_array($viewerType, ['trial', 'pro', 'lifetime'], true))
                        <x-heroicon-o-pause
                            class="routine-player__exercise-icon"
                            width="18"
                            height="18"
                            aria-hidden="true"
                            x-show="
                                isPlaying
                                && activeExerciseIndex === {{ $loop->index }}
                            "
                            x-cloak
                        />

                        <x-heroicon-o-play
                            class="routine-player__exercise-icon"
                            width="18"
                            height="18"
                            aria-hidden="true"
                            x-show="
                                !isPlaying
                                || activeExerciseIndex !== {{ $loop->index }}
                            "
                        />
                    @else
                        <x-heroicon-o-play
                            class="routine-player__exercise-icon"
                            width="18"
                            height="18"
                            aria-hidden="true"
                        />

                    @endif
                </button>

                @if ($step->alpha_tex)
                    <button class="routine-player__exercise-details-toggle button" type="button"
                        :aria-expanded="detailsOpen.toString()"
                        @click="
                            detailsOpen = !detailsOpen;

                            if (detailsOpen) {
                                $nextTick(() => {
                                    window.dispatchEvent(new CustomEvent('alphatab:mount', {
                                        detail: { element: $refs.alphaTab }
                                    }));
                                });
                            } else {
                                window.dispatchEvent(new Event('alphatab:stop'));
                            }
                        "
                    >
                        <span x-text="detailsOpen ? 'Hide exercise' : 'View exercise'">View exercise</span>
                    </button>

                    <div class="routine-player__exercise-details" x-show="detailsOpen" x-cloak>
                        <div x-ref="alphaTab"
                            data-alphatab
                            data-exercise-index="{{ $loop->index }}"
                            data-alpha-tex="{{ base64_encode($step->alpha_tex) }}"
                            data-bpm="{{ $step->bpm }}">
                        </div>

                        <div class="routine-player__exercise-audio-controls" x-data="{ looping: true, hearing: false }">
                            <button class="badge badge--neutral" type="button"
                                @click="
                                    hearing = !hearing;

                                    window.dispatchEvent(new CustomEvent('alphatab:play-pause', {
                                        detail: { element: $refs.alphaTab }
                                    }));
                                "
                            >
                                <span x-text="hearing ? 'Mute' : 'Listen'">Listen</span>
                            </button>

                            <button class="badge badge--neutral" type="button"
                                :aria-pressed="looping.toString()"
                                @click="
                                    looping = !looping;

                                    window.dispatchEvent(new CustomEvent('alphatab:toggle-loop', {
                                        detail: { element: $refs.alphaTab }
                                    }));
                                "
                            >
                                <span x-text="looping ? 'Enable loop' : 'Disable loop'">Loop on</span>
                            </button>
                        </div>
                    </div>
                @endif
            </li>
        @endforeach
    </ol>
</section>