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

            <li class="routine-player__exercise">
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
                            <span>{{ $durationLabel }}</span>
                        @endif
                    </span>

                    <x-heroicon-o-play
                        class="routine-player__exercise-icon"
                        width="18"
                        height="18"
                        aria-hidden="true"
                    />
                </button>
            </li>
        @endforeach
    </ol>
</section>