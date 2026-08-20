<section class="routine-player__metronome"
    @if ($coverUrl)
        style="--routine-cover-image: url('{{ $coverUrl }}');"
    @endif
>
    <div class="routine-player__metronome-content">
        <header class="routine-player__current">
            <span class="routine-player__eyebrow">
                Current exercise
            </span>

            <h2 class="routine-player__current-name" x-text="steps[currentIndex]?.name ?? ''">
                {{ $initialStepName }}
            </h2>
        </header>

        <div class="routine-player__beats" aria-label="Metronome beats">
            @foreach (range(1, 4) as $beat)
                <span
                    @class([
                        'routine-player__beat',
                        'routine-player__beat--active' => $beat === 1,
                    ])
                >
                    {{ $beat }}
                </span>
            @endforeach
        </div>

        <div class="routine-player__tempo">
            <strong class="routine-player__tempo-value" x-text="metronome.bpm">
                {{ $initialBpm }}
            </strong>

            <span class="routine-player__tempo-unit">
                BPM
            </span>
        </div>

        <input
            class="routine-player__tempo-control"
            type="range"
            min="30"
            max="400"
            x-model.number="metronome.bpm"
            disabled
            aria-label="Tempo"
        />

        @if (in_array($viewerType, ['guest', 'free'], true))
            <button
                class="routine-player__start button"
                data-type="primary"
                type="button"
                @click="useTemplateAsLocalRoutine()"
            >
                Use this routine
            </button>
        @else
            <button
                class="routine-player__start button"
                data-type="primary"
                type="button"

                @click="
                    if (isPlaying) {
                        stop('manual')
                    } else {
                        startExercise(
                            activeExerciseIndex ?? currentIndex
                        )
                    }
                "
            >
                <span
                    x-text="isPlaying ? 'Stop' : 'Start'"
                >
                    Start
                </span>
            </button>
        @endif
    </div>
</section>