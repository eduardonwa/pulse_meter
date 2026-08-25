<section class="routine-player__metronome" data-disable-space-shortcut
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

            <span class="routine-player__progress" x-text="`${currentIndex + 1} of ${steps.length}`">
                1 of {{ $template->steps->count() }}
            </span>
        </header>

        @if (in_array($viewerType, ['guest', 'free'], true))
            <button class="routine-player__start button"
                data-type="play-metronome"
                type="button"
                @click="useTemplateAsLocalRoutine()"
            >
                Use this routine
            </button>
        @else
            <button class="routine-player__start button"
                data-type="play-metronome"
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